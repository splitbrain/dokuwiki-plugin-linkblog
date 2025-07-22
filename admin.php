<?php

use dokuwiki\Form\Form;
use dokuwiki\Extension\AdminPlugin;

/**
 * DokuWiki Plugin linkblog (Admin Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <andi@splitbrain.org>
 */
class admin_plugin_linkblog extends AdminPlugin
{
    /**
     * @inheritdoc
     */
    public function handle()
    {
        global $INPUT;
        /** @var helper_plugin_linkblog $hlp */
        $hlp = plugin_load('helper', 'linkblog');

        $feeds = $INPUT->arr('feed');
        foreach ($feeds as $fid => $feed) {
            if ($feed['name']) {
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
    public function html()
    {
        /** @var helper_plugin_linkblog $hlp */
        $hlp = plugin_load('helper', 'linkblog');

        $feeds = $hlp->loadFeeds(false);
        $feeds[] = [
            'id' => 0,
            'name' => '',
            'feed' => '',
            'usereadability' => 1,
            'usecontent' => 1,
            'enabled' => 1,
            'filter' => '',
            'repl' => '',
            'with' => ''
        ];

        $form = new Form();
        $form->setHiddenField('page', 'linkblog');
        $form->addHTML('<table class="inline">');

        $form->addHTML('<tr>');
        $form->addHTML('<th>Name</th>');
        $form->addHTML('<th>Feed-URL</th>');
        $form->addHTML('<th></th>');
        $form->addHTML('</tr>');

        foreach ($feeds as $feed) {
            $form->addTagOpen('tr');

            $form->addTagOpen('td');
            $form->addTextInput('feed[' . $feed['id'] . '][name]')->useInput(false)->val($feed['name']);
            $form->addTagClose('td');

            $form->addTagOpen('td');
            $form->addTextInput('feed[' . $feed['id'] . '][feed]')->useInput(false)->val($feed['feed']);
            $form->addTagClose('td');

            $form->addTagOpen('td');

            $cb = $form->addCheckbox('feed[' . $feed['id'] . '][usereadability]', 'Readability')->useInput(false);
            if ($feed['usereadability']) $cb->attr('checked', 'checked');

            $cb = $form->addCheckbox('feed[' . $feed['id'] . '][usecontent]', 'Content')->useInput(false);
            if ($feed['usecontent']) $cb->attr('checked', 'checked');

            $cb = $form->addCheckbox('feed[' . $feed['id'] . '][enabled]', 'Enabled')->useInput(false);
            if ($feed['enabled']) $cb->attr('checked', 'checked');

            $form->addHTML('<br />');
            $form->addTextInput('feed[' . $feed['id'] . '][filter]', 'Filter')->useInput(false)->val($feed['filter']);

            $form->addHTML('<br />');
            $form->addTextInput('feed[' . $feed['id'] . '][repl]', 'Replace')->useInput(false)->val($feed['repl']);

            $form->addHTML('<br />');
            $form->addTextInput('feed[' . $feed['id'] . '][with]', 'with')->useInput(false)->val($feed['with']);

            $form->addTagClose('td');

            $form->addTagClose('tr');
        }

        $form->addTagClose('table');
        $form->addButton('do[admin]', 'Save')->attr('type', 'submit');

        echo $form->toHTML();

        $this->lastentries();
    }

    /**
     * Print the last couple of entries
     */
    protected function lastentries()
    {
        /** @var helper_plugin_linkblog $hlp */
        $hlp = plugin_load('helper', 'linkblog');

        $items = $hlp->getItems($this->getConf('limit'));
        foreach ($items as $item) {
            echo $hlp->formatItem($item);
        }
    }
}
