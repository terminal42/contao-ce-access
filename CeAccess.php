<?php

/*
 * ce-access Extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2008-2017, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       http://github.com/terminal42/contao-ce-access
 */


class CeAccess extends Backend
{

    public function __construct()
    {
        parent::__construct();

        $this->import('BackendUser', 'User');
    }


    /**
     * Remove available content elements
     *
     * @access    public
     * @param     object
     * @return    void
     */
    public function filterContentElements($dc)
    {
        if ($this->User->isAdmin)
            return;

        $arrElements = deserialize($this->User->elements, true);
        $arrKeys = array_flip($arrElements);
        $arrConfig = $GLOBALS['TL_CTE'];

        foreach ($arrConfig as $group => $v)
        {
            $arrConfig[$group] = array_intersect_key($arrConfig[$group], $arrKeys);

            if (empty($arrConfig[$group])) {
                unset($arrConfig[$group]);
            }
        }

        // No content elements possible, disable new elements
        if (empty($arrConfig)) {
            $GLOBALS['TL_DCA']['tl_content']['config']['closed'] = true;
            $GLOBALS['TL_DCA']['tl_content']['config']['notEditable'] = true;
            $GLOBALS['TL_DCA']['tl_content']['config']['notDeletable'] = true;
            unset($GLOBALS['TL_DCA']['tl_content']['list']['global_operations']['all']);
        }

        // Default element has been hidden
        elseif (!in_array($GLOBALS['TL_DCA']['tl_content']['fields']['type']['default'], $arrElements)) {
            reset($arrConfig);
            $GLOBALS['TL_DCA']['tl_content']['fields']['type']['default'] = @key(@current($arrConfig));
            $GLOBALS['TL_DCA']['tl_content']['palettes']['default'] = $GLOBALS['TL_DCA']['tl_content']['palettes'][@key(@current($arrConfig))];
        }

        if ($this->Input->get('act') != '' && $this->Input->get('act') != 'select') {

            $GLOBALS['TL_CTE'] = $arrConfig;
            $session = $this->Session->getData();

            // Set allowed content element IDs (edit multiple)
            if (!empty($session['CURRENT']['IDS']) && is_array($session['CURRENT']['IDS']))
            {
                $session['CURRENT']['IDS'] = $this->Database->execute("SELECT id FROM tl_content WHERE id IN (" . implode(',', $session['CURRENT']['IDS']) . ") AND type IN ('" . implode("','", $arrElements) . "')")->fetchEach('id');
            }

            // Set allowed clipboard IDs
            if (isset($session['CLIPBOARD']['tl_content']) && is_array($session['CLIPBOARD']['tl_content']['id']) && count($session['CLIPBOARD']['tl_content']['id']))
            {
                $session['CLIPBOARD']['tl_content']['id'] = $this->Database->execute("SELECT id FROM tl_content WHERE id IN (" . implode(',', $session['CLIPBOARD']['tl_content']['id']) . ") AND type IN ('" . implode("','", $arrElements) . "') ORDER BY sorting")->fetchEach('id');
            }

            // Overwrite session
            $this->Session->setData($session);

            if (!in_array($this->Input->get('act'), array('show', 'create', 'select', 'editAll')) && !($this->Input->get('act') == 'paste' && $this->Input->get('mode') == 'create'))
            {
                $objElement = $this->Database->prepare("SELECT type FROM tl_content WHERE id=?")
                                             ->limit(1)
                                             ->execute($dc->id);

                if ($objElement->numRows && !in_array($objElement->type, $arrElements)) {
                    $this->log('Attempt to access restricted content element "' . $objElement->type . '"', 'CeAccess filterContentElements()', TL_ACCESS);
                    $this->redirect($this->Environment->script.'?act=error');
                }
            }
        }
    }


    /**
     * Hide buttons for disabled content elements
     */
    public function hideButton($row, $href, $label, $title, $icon, $attributes)
    {
        return ($this->User->isAdmin || in_array($row['type'], (array) $this->User->elements)) ? '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.$this->generateImage($icon, $label).'</a> ' : '';
    }


    /**
     * Hide delete button for disabled content elements
     */
    public function deleteButton($row)
    {
        if ($this->User->isAdmin || in_array($row['type'], (array) $this->User->elements))
        {
            return call_user_func_array(['tl_content', 'deleteElement'], func_get_args());
        }

        return '';
    }


    /**
     * Hide toggle button for disabled content elements
     */
    public function toggleButton($row, $href, $label, $title, $icon, $attributes)
    {
        if ($this->User->isAdmin || in_array($row['type'], (array) $this->User->elements))
        {
            return call_user_func_array(['tl_content', 'toggleIcon'], func_get_args());
        }

        return '';
    }

    /**
     * Return all content elements as array
     * @return    array
     */
    public static function getContentElements()
    {
        static $arrElements;

        if (null === $arrElements) {

            $arrElements = array();

            foreach ($GLOBALS['TL_CTE'] as $k => $v) {
                $arrElements[$k] = array();

                foreach (array_keys($v) as $kk) {
                    $arrElements[$k][] = $kk;
                }
            }
        }

        return $arrElements;
    }
}
