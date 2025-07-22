<?php

use dokuwiki\Extension\CLIPlugin;
use splitbrain\phpcli\Options;

/**
 * DokuWiki Plugin linkblog (CLI Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <andi@splitbrain.org>
 */
class cli_plugin_linkblog extends CLIPlugin
{
    /** @inheritDoc */
    protected function setup(Options $options)
    {
        $options->setHelp('Update all feeds');
    }

    /** @inheritDoc */
    protected function main(Options $options)
    {
        $this->info('starting feed fetching');

        /** @var helper_plugin_linkblog $hlp */
        $hlp = plugin_load('helper', 'linkblog');

        $feeds = $hlp->loadFeeds(true);
        foreach ($feeds as $feed) {
            $this->info('Checking ' . $feed['name']);

            $rss = new FeedParser();
            $rss->set_feed_url($feed['feed']);
            $rss->init();

            $count = 0;
            foreach ($rss->get_items() as $item) {
                /** @var $item SimplePie_Item */

                if ($hlp->storeArticle($item, $feed)) {
                    $this->success($item->get_permalink());
                }

                // we only pull the newest few items
                if ($count++ > 8) break;
            }
        }
    }
}
