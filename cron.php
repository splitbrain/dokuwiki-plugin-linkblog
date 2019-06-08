#!/usr/bin/php
<?php

use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Options;

if(!defined('DOKU_INC')) define('DOKU_INC', realpath(dirname(__FILE__) . '/../../../') . '/');
define('NOSESSION', 1);
require_once(DOKU_INC . 'inc/init.php');

class LinkblogCron extends CLI {

    /**
     * Register options and arguments on the given $options object
     *
     * @param Options $options
     * @return void
     */
    protected function setup(Options $options) {
        $options->setHelp('Update all feeds');
    }

    /**
     * Your main program
     *
     * Arguments and options have been parsed when this is run
     *
     * @param Options $options
     * @return void
     */
    protected function main(Options $options) {
        $this->info('starting feed fetching');

        /** @var helper_plugin_linkblog $hlp */
        $hlp = plugin_load('helper', 'linkblog');

        $feeds = $hlp->loadFeeds(true);
        foreach($feeds as $feed) {
            $this->info('Checking ' . $feed['name']);

            $rss = new FeedParser();
            $rss->set_feed_url($feed['feed']);
            $rss->init();

            $count = 0;
            foreach($rss->get_items() as $item) {
                /** @var $item SimplePie_Item */

                if($hlp->storeArticle($item, $feed)) {
                    $this->success($item->get_permalink());
                }

                // we only pull the newest few items
                if($count++ > 8) break;
            }
        }
    }

}

$cron = new LinkblogCron();
$cron->run();
