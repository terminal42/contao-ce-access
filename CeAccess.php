<?php

/*
 * ce-access Extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2008-2017, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       http://github.com/terminal42/contao-ce-access
 */

class CeAccess
{
    /**
     * Remove available content elements.
     *
     * @param \DataContainer $dc
     */
    public function filterContentElements($dc)
    {
        if (\BackendUser::getInstance()->isAdmin) {
            return;
        }

        $arrElements = deserialize(\BackendUser::getInstance()->elements, true);
        $arrKeys = array_flip($arrElements);
        $arrConfig = $GLOBALS['TL_CTE'];

        foreach ($arrConfig as $group => $v) {
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
        elseif (!in_array($GLOBALS['TL_DCA']['tl_content']['fields']['type']['default'], $arrElements, true)) {
            reset($arrConfig);
            $GLOBALS['TL_DCA']['tl_content']['fields']['type']['default'] = @key(@current($arrConfig));
            $GLOBALS['TL_DCA']['tl_content']['palettes']['default'] = $GLOBALS['TL_DCA']['tl_content']['palettes'][@key(@current($arrConfig))];
        }

        if (\Input::get('act') !== '' && \Input::get('act') !== 'select') {
            $GLOBALS['TL_CTE'] = $arrConfig;
            $session = \Session::getInstance()->getData();

            // Set allowed content element IDs (edit multiple)
            if (!empty($session['CURRENT']['IDS']) && is_array($session['CURRENT']['IDS'])) {
                $session['CURRENT']['IDS'] = \Database::getInstance()->execute('
                    SELECT id 
                    FROM tl_content 
                    WHERE id IN ('.implode(',', $session['CURRENT']['IDS']).") 
                        AND type IN ('".implode("','", $arrElements)."')"
                )->fetchEach('id');
            }

            // Set allowed clipboard IDs
            if (isset($session['CLIPBOARD']['tl_content'])
                && is_array($session['CLIPBOARD']['tl_content']['id'])
                && count($session['CLIPBOARD']['tl_content']['id'])
            ) {
                $session['CLIPBOARD']['tl_content']['id'] = \Database::getInstance()
                    ->execute('
                        SELECT id FROM tl_content 
                        WHERE id IN ('.implode(',', $session['CLIPBOARD']['tl_content']['id']).") 
                            AND type IN ('".implode("','", $arrElements)."') 
                        ORDER BY sorting
                    ")
                    ->fetchEach('id')
                ;
            }

            // Overwrite session
            \Session::getInstance()->setData($session);

            if (!in_array(\Input::get('act'), array('show', 'create', 'select', 'editAll'), true)
                && !(\Input::get('act') === 'paste' && \Input::get('mode') === 'create')
            ) {
                $objElement = \Database::getInstance()
                    ->prepare('SELECT type FROM tl_content WHERE id=?')
                    ->execute($dc->id)
                ;

                if ($objElement->numRows && !in_array($objElement->type, $arrElements, true)) {
                    \System::log(
                        'Attempt to access restricted content element "'.$objElement->type.'"',
                        'CeAccess filterContentElements()',
                        TL_ACCESS
                    );

                    \Controller::redirect(\Environment::get('script').'?act=error');
                }
            }
        }
    }

    /**
     * Hide buttons for disabled content elements.
     *
     * @param mixed $row
     * @param mixed $href
     * @param mixed $label
     * @param mixed $title
     * @param mixed $icon
     * @param mixed $attributes
     *
     * @return string
     */
    public function hideButton($row, $href, $label, $title, $icon, $attributes)
    {
        if (!\BackendUser::getInstance()->isAdmin
            && !in_array($row['type'], (array) \BackendUser::getInstance()->elements, true)
        ) {
            return '';
        }

        return sprintf(
            '<a href="%s" title="%s"%s>%s</a> ',
            \Backend::addToUrl($href.'&amp;id='.$row['id']),
            specialchars($title),
            $attributes,
            \Image::getHtml($icon, $label)
        );
    }

    /**
     * Hide delete button for disabled content elements.
     *
     * @param array $row
     *
     * @return string
     */
    public function deleteButton($row)
    {
        if (\BackendUser::getInstance()->isAdmin
            || in_array($row['type'], (array) \BackendUser::getInstance()->elements, true)
        ) {
            return call_user_func_array(array(new tl_content(), 'deleteElement'), func_get_args());
        }

        return '';
    }

    /**
     * Hide toggle button for disabled content elements.
     *
     * @param array $row
     *
     * @return string
     */
    public function toggleButton($row)
    {
        if (\BackendUser::getInstance()->isAdmin
            || in_array($row['type'], (array) \BackendUser::getInstance()->elements, true)
        ) {
            return call_user_func_array(array(new tl_content(), 'toggleIcon'), func_get_args());
        }

        return '';
    }

    /**
     * Return all content elements as array.
     *
     * @return array
     */
    public static function getContentElements()
    {
        static $arrElements;

        if (null === $arrElements) {
            $arrElements = array();

            foreach ($GLOBALS['TL_CTE'] as $k => $v) {
                $arrElements[$k] = array();

                foreach ($v as $kk => $vv) {
                    $arrElements[$k][] = $kk;
                }
            }
        }

        return $arrElements;
    }
}
