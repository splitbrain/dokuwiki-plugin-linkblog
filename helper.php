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

    /**
     * Get a list of feed items, optionally filtered
     *
     * @param int $max    number of items maximum
     * @param int $newest newest allowed item
     * @param int $oldest oldest allowed item
     * @return array
     */
    public function getItems($max=20, $newest=0, $oldest=0) {
        $sqlite = $this->getDB();
        if(!$sqlite) return array();


        $values = array();
        $where = '';
        if($newest){
            $where .= ' AND published < ?';
            $values[] = $newest;
        }
        if($oldest){
            $where .= ' AND published >= ?';
            $values[] = $oldest;
        }
        $values[] = $max;


        $sql = "SELECT title, url, description, content, published, name as source
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
     * @param int    $id set 0 for adding a new feed
     * @param string $name
     * @param string $feedurl
     * @param bool   $useReadability
     * @param bool   $useContent
     * @param bool   $isEnabled
     * @return bool
     */
    public function editFeed($id, $name, $feedurl, $useReadability, $useContent, $isEnabled) {
        $sqlite = $this->getDB();
        if(!$sqlite) return false;

        $values = array($name, $feedurl, (int) $useReadability, (int) $useContent, (int) $isEnabled);
        if($id) {
            array_unshift($values, $id);
            $sql    = "REPLACE INTO sources (id, name, feed, usereadability, usecontent, enabled) VALUES (?,?,?,?,?,?)";
        } else {
            $sql = "INSERT INTO sources (name, feed, usereadability, usecontent, enabled) VALUES (?,?,?,?,?)";
        }

        $sqlite->query($sql, $values);
        return true;
    }

    /**
     * Save an article
     *
     * @param SimplePie_Item $item
     * @param int            $src
     * @param bool           $useReadbility
     * @param bool           $useContent
     * @return bool
     */
    public function storeArticle(SimplePie_Item $item, $src, $useReadbility = true, $useContent = true) {
        $sqlite = $this->getDB();
        if(!$sqlite) return false;

        $url = $item->get_permalink();

        // check if we have this article already
        $sql   = "SELECT id FROM items WHERE id = ?";
        $res   = $sqlite->query($sql, md5($url));
        $found = (bool) $sqlite->res2single($res);
        $sqlite->res_close($res);
        if($found) return false;

        $content = '';
        if($useReadbility) {
            // fetch the article's content through readabilities filter
            $readability = 'http://www.readability.com/m?url='.rawurldecode($url);

            $http    = new DokuHTTPClient();
            $content = $http->get($readability);
            if(preg_match('/(<section id="rdb-article-content" dir="ltr">)(.*?)(<\/section)/s', $content, $m)) {
                $content = $m[2];
            } else {
                $content = '';
            }
        }

        if($useContent) {
            $prefix = $item->get_content();
            if($content) {
                $prefix = '<blockquote>'.$prefix.'<hr></blockquote>';
            }

            $content = $prefix.$content;
        }

        $sql = "INSERT INTO items (id, src, published, fetched, title, url, description, content)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $sqlite->query(
            $sql,
            md5($url),
            $src,
            $item->get_date('U'),
            time(),
            $item->get_title(),
            $url,
            $item->get_content(),
            $content
        );

        return true;
    }

}

// vim:ts=4:sw=4:et:
