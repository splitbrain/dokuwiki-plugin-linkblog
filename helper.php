<?php
/**
 * DokuWiki Plugin linkblog (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <andi@splitbrain.org>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class helper_plugin_linkblog extends DokuWiki_Plugin {

    /** @var helper_plugin_sqlite $sqlite */
    protected $sqlite = null;

    /**
     * Access the SQLite plugin
     *
     * @return helper_plugin_sqlite|null
     */
    protected function getDB() {
        if(!is_null($this->sqlite)) return $this->sqlite;

        /** @var helper_plugin_sqlite $sqlite */
        $sqlite = plugin_load('helper', 'sqlite');
        if(!$sqlite) {
            msg('The linkblog plugin requires the sqlite plugin', -1);
            return null;
        }

        if(!$sqlite->init('linkblog', __DIR__.'/db')) {
            return null;
        }

        $this->sqlite = $sqlite;
        return $this->sqlite;
    }

    /**
     * Get a list of all feeds
     *
     * @param bool $enabledonly
     * @return array
     */
    public function loadFeeds($enabledonly = true) {
        $sqlite = $this->getDB();
        if(!$sqlite) return array();

        $sql  = "SELECT * FROM sources";
        if($enabledonly) $sql .= ' WHERE enabled = 1';
        $res  = $sqlite->query($sql);
        $data = $sqlite->res2arr($res);
        $sqlite->res_close($res);
        return $data;
    }


    public function formatItem($item) {

        $urlparts = parse_url($item['url']);
        $ico = 'http://'.$urlparts['host'][0].'.getfavicon.appspot.com/'.rawurlencode($urlparts['scheme'].'://'.$urlparts['host']);

        $html  = '<div class="plugin-linkblog">';
        $html .= '<a href="'.$item['url'].'">';
        $html .= '<img src="'.$ico.'" width="16" height="16" alt="" />';
        $html .= hsc($item['title']);
        $html .= '</a>';
        $html .= ' <span>'.date('Y-m-d', $item['published']).'</span>';
        if($item['usecontent']){
            $content = trim(strip_tags($item['description']));
            if($content) {
                $html .= '<blockquote>'.hsc($content).'</blockquote>';
            }
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Get a list of feed items, optionally filtered
     *
     * @param int $max    number of items maximum
     * @param int $newerthan newest allowed item
     * @param int $olderthan oldest allowed item
     * @return array
     */
    public function getItems($max=20, $newerthan=0, $olderthan=0) {
        $sqlite = $this->getDB();
        if(!$sqlite) return array();


        $values = array();
        $where = '';
        if($newerthan){
            $where .= ' AND published >= ?';
            $values[] = $newerthan;
        }
        if($olderthan){
            $where .= ' AND published < ?';
            $values[] = $olderthan;
        }
        $values[] = $max;


        $sql = "SELECT title, url, description, content, published, name as source, usecontent
                  FROM items A, sources B
                 WHERE A.src = B.ID
                       $where
              ORDER BY published DESC
                 LIMIT ?";

        $res = $sqlite->query($sql, $values);
        $data = $sqlite->res2arr($res);
        $sqlite->res_close($res);

        return $data;
    }

    /**
     * @param int $id set 0 for adding a new feed
     * @param array $feed feed data
     * @return bool
     */
    public function editFeed($id, $feed) {
        $sqlite = $this->getDB();
        if(!$sqlite) return false;

        $values = array();
        $values[] = $feed['name'];
        $values[] = $feed['feed'];
        $values[] = (int) $feed['usereadability'];
        $values[] = (int) $feed['usecontent'];
        $values[] = (int) $feed['enabled'];
        $values[] = $feed['filter'];
        $values[] = $feed['repl'];
        $values[] = $feed['with'];

        if($id) {
            array_unshift($values, $id);
            $sql    = "REPLACE INTO sources (id, name, feed, usereadability, usecontent, enabled, filter, repl, with) VALUES (?,?,?,?,?,?,?,?,?)";
        } else {
            $sql = "INSERT INTO sources (name, feed, usereadability, usecontent, enabled, filter, repl, with) VALUES (?,?,?,?,?,?,?,?)";
        }

        $sqlite->query($sql, $values);
        return true;
    }

    /**
     * Save an article
     *
     * @param SimplePie_Item $item
     * @param array $feed The feed options
     * @return bool
     */
    public function storeArticle(SimplePie_Item $item, $feed) {
        $sqlite = $this->getDB();
        if(!$sqlite) return false;

        // check if this should be filtered out
        $title = $item->get_title();
        if($feed['filter'] && !preg_match('/'.$feed['filter'].'/', $title)) return false;


        $url = $item->get_permalink();

        // check if we have this article already
        $sql   = "SELECT id FROM items WHERE id = ?";
        $res   = $sqlite->query($sql, md5($url));
        $found = (bool) $sqlite->res2single($res);
        $sqlite->res_close($res);
        if($found) return false;

        $http    = new DokuHTTPClient();
        // unshorten the URL for storage
        if($this->getConf('unshortenapikey')) {
            $fullurl = $http->get('http://api.unshorten.it?shortURL='.rawurlencode($url).'&apiKey='.$this->getConf('unshortenapikey'));
            if(!$fullurl) $fullurl = $url;
            if(strtolower(substr($fullurl,0,4)) != 'http') $fullurl = $url;
        }else {
            $fullurl = $url;
        }

        // adjust title
        if($feed['repl']) $title = preg_replace('/'.$feed['repl'].'/', $feed['with'], $title);

        $content = '';
        if($feed['usereadbility']) {
            // fetch the article's content through readabilities filter
            $readability = 'http://www.readability.com/m?url='.rawurldecode($fullurl);

            $content = $http->get($readability);
            if(preg_match('/(<section id="rdb-article-content" dir="ltr">)(.*?)(<\/section)/s', $content, $m)) {
                $content = $m[2];
            } else {
                $content = '';
            }

            if($feed['usecontent']) {
                $prefix = $item->get_content();
                if($content) {
                    $prefix = '<blockquote>'.$prefix.'<hr></blockquote>';
                }

                $content = $prefix.$content;
            }
        }else {
            $content = $item->get_content();
        }

        $sql = "INSERT INTO items (id, src, published, fetched, title, url, description, content)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $sqlite->query(
            $sql,
            md5($url),
            $feed['id'],
            $item->get_date('U'),
            time(),
            $title,
            $fullurl,
            $item->get_content(),
            $content
        );

        return true;
    }

}

// vim:ts=4:sw=4:et:
