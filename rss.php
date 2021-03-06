<?php
if(!defined('DOKU_INC')) define('DOKU_INC', realpath(dirname(__FILE__) . '/../../../') . '/');
define('NOSESSION', 1);
require_once(DOKU_INC . 'inc/init.php');

/** @var helper_plugin_linkblog $hlp */
$hlp = plugin_load('helper', 'linkblog');

$items = $hlp->getItems(5);

$feed = new UniversalFeedCreator();
$feed->title = $hlp->getConf('feedtitle');
foreach($items as $item) {
    $fitem = new FeedItem();
    $fitem->link = $item['url'];
    $fitem->title = $item['title'];
    $fitem->date = $item['published'];
    $fitem->description = $item['content'];
    $fitem->guid = $item['url'];

    $feed->addItem($fitem);
}

header('Content-Type: text/xml');
echo $feed->createFeed('RSS2.0');
