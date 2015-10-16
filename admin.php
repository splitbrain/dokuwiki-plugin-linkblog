<?php
/**
 * DokuWiki Plugin linkblog (Admin Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <andi@splitbrain.org>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class admin_plugin_linkblog extends DokuWiki_Admin_Plugin {

    /**
     * @inheritdoc
     */
    function handle() {
        global $INPUT;
        /** @var helper_plugin_linkblog $hlp */
        $hlp = plugin_load('helper', 'linkblog');

        $feeds = $INPUT->arr('feed');
        foreach($feeds as $fid => $feed) {
            if($feed['name']) {
                $hlp->editFeed(
                    $fid,
                    $feed
                );
            }
        }
    }

    /**
     * @inheritdoc
     */
    function html() {
        /** @var helper_plugin_linkblog $hlp */
        $hlp = plugin_load('helper', 'linkblog');

        $feeds = $hlp->loadFeeds(false);
        $feeds[] = array(
            'id' => 0,
            'name' => '',
            'feed' => '',
            'usereadability' => 1,
            'usecontent' => 1,
            'enabled' => 1,
            'filter' => '',
            'repl' => '',
            'with' => '',
        );

        $form = new Doku_Form(array());
        $form->addHidden('page', 'linkblog');
        $form->addElement('<table class="inline">');

        $form->addElement('<tr>');
        $form->addElement('<th>Name</th>');
        $form->addElement('<th>Feed-URL</th>');
        $form->addElement('<th></th>');
        $form->addElement('</tr>');

        foreach($feeds as $feed) {
            $form->addElement('<tr>');

            $form->addElement('<td>');
            $form->addElement(form_makeTextField('feed[' . $feed['id'] . '][name]', $feed['name'], ''));
            $form->addElement('</td>');

            $form->addElement('<td>');
            $form->addElement(form_makeTextField('feed[' . $feed['id'] . '][feed]', $feed['feed'], ''));
            $form->addElement('</td>');

            $form->addElement('<td>');
            if($feed['usereadability']) {
                $check = array('checked' => 'checked');
            } else {
                $check = array();
            }
            $form->addElement(form_makeCheckboxField('feed[' . $feed['id'] . '][usereadability]', 1, 'Readability', '', '', $check));

            if($feed['usecontent']) {
                $check = array('checked' => 'checked');
            } else {
                $check = array();
            }
            $form->addElement(form_makeCheckboxField('feed[' . $feed['id'] . '][usecontent]', 1, 'Content', '', '', $check));
            if($feed['enabled']) {
                $check = array('checked' => 'checked');
            } else {
                $check = array();
            }
            $form->addElement(form_makeCheckboxField('feed[' . $feed['id'] . '][enabled]', 1, 'Enabled', '', '', $check));

            $form->addElement('<br />');
            $form->addElement(form_makeField('text', 'feed[' . $feed['id'] . '][filter]', $feed['filter'], 'Filter'));

            $form->addElement('<br />');
            $form->addElement(form_makeField('text', 'feed[' . $feed['id'] . '][repl]', $feed['repl'], 'Replace'));
            $form->addElement('<br />');
            $form->addElement(form_makeField('text', 'feed[' . $feed['id'] . '][with]', $feed['with'], 'with'));

            $form->addElement('</td>');

            $form->addElement('</tr>');
        }

        $form->addElement('</table>');
        $form->addElement(form_makeButton('submit', 'admin', 'Save'));
        $form->printForm();

        $this->lastentries();
    }

    /**
     * Print the last couple of entries
     */
    protected function lastentries() {
        /** @var helper_plugin_linkblog $hlp */
        $hlp = plugin_load('helper', 'linkblog');

        $items = $hlp->getItems($this->getConf('limit'));
        foreach($items as $item) {
            echo $hlp->formatItem($item);
        }

    }
}
