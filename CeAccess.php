<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2012 Leo Feyer
 * Formerly known as TYPOlight Open Source CMS.
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 * PHP version 5
 *
 * @copyright  terminal42 gmbh 2009-2013
 * @author     Andreas Schempp <andreas.schempp@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */
class CeAccess
{

    /**
     * Remove available content elements
     *
     * @param \DataContainer $dc
     */
    public function filterContentElements($dc)
    {
        if (\BackendUser::getInstance()->isAdmin) {
            return;
        }

        $arrElements = $this->getAllowedElements();
        $arrKeys     = array_flip($arrElements);
        $arrConfig   = (array) $GLOBALS['TL_CTE'];

        foreach ($arrConfig as $group => $v) {
            $arrConfig[$group] = array_intersect_key($arrConfig[$group], $arrKeys);

            if (empty($arrConfig[$group])) {
                unset($arrConfig[$group]);
            }
        }

        if (empty($arrConfig)) {
            // No content elements possible, disable new elements
            $GLOBALS['TL_DCA']['tl_content']['config']['closed']       = true;
            $GLOBALS['TL_DCA']['tl_content']['config']['notEditable']  = true;
            $GLOBALS['TL_DCA']['tl_content']['config']['notDeletable'] = true;
            unset($GLOBALS['TL_DCA']['tl_content']['list']['global_operations']['all']);
        } elseif (!in_array($GLOBALS['TL_DCA']['tl_content']['fields']['type']['default'], $arrElements, true)) {
            // Default element has been hidden
            reset($arrConfig);
            $GLOBALS['TL_DCA']['tl_content']['fields']['type']['default'] = @key(@current($arrConfig));
            $GLOBALS['TL_DCA']['tl_content']['palettes']['default'] = $GLOBALS['TL_DCA']['tl_content']['palettes'][@key(@current($arrConfig))];
        }

        if ('' !== (string) \Input::get('act') && 'select' !== \Input::get('act')) {
            $GLOBALS['TL_CTE'] = $arrConfig;
            $session = \Session::getInstance()->getData();

            // Set allowed content element IDs (edit multiple)
            if (!empty($session['CURRENT']['IDS']) && is_array($session['CURRENT']['IDS'])) {
                $session['CURRENT']['IDS'] = \Database::getInstance()
                    ->execute(
                        'SELECT id 
                        FROM tl_content 
                        WHERE id IN (' . implode(',', $session['CURRENT']['IDS']) . ") 
                            AND type IN ('" . implode("','", $arrElements) . "')"
                    )
                    ->fetchEach('id')
                ;
            }

            // Set allowed clipboard IDs
            if (isset($session['CLIPBOARD']['tl_content'])
                && is_array($session['CLIPBOARD']['tl_content']['id'])
                && count($session['CLIPBOARD']['tl_content']['id'])
            ) {
                $session['CLIPBOARD']['tl_content']['id'] = \Database::getInstance()
                    ->execute(
                        'SELECT id 
                        FROM tl_content 
                        WHERE id IN (' . implode(',', $session['CLIPBOARD']['tl_content']['id']) . ") 
                            AND type IN ('" . implode("','", $arrElements) . "') 
                        ORDER BY sorting"
                    )
                    ->fetchEach('id')
                ;
            }

            // Overwrite session
            \Session::getInstance()->setData($session);

            if (!in_array(\Input::get('act'), array('show', 'create', 'select', 'editAll'), true)
                && !('paste' === \Input::get('act') && 'create' === \Input::get('mode'))
            ) {
                $objElement = \Database::getInstance()
                    ->prepare('SELECT type FROM tl_content WHERE id=?')
                    ->execute($dc->id)
                ;

                if ($objElement->numRows && !in_array($objElement->type, $arrElements, true)) {
                    \System::log(
                        'Attempt to access restricted content element "' . $objElement->type . '"',
                        'CeAccess filterContentElements()',
                        TL_ACCESS
                    );

                    \Controller::redirect(\Environment::get('script') . '?act=error');
                }
            }
        }
    }

    /**
     * Hide buttons for disabled content elements
     *
     * @param array  $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     *
     * @return string
     */
    public function hideButton($row, $href, $label, $title, $icon, $attributes)
    {
        if (!\BackendUser::getInstance()->isAdmin
            && !in_array($row['type'], $this->getAllowedElements(), true)
        ) {
            return '';
        }

        return sprintf(
            '<a href="%s" title="%s" %s>%s</a> ',
            \Backend::addToUrl($href.'&amp;id='.$row['id']),
            specialchars($title),
            $attributes,
            \Image::getHtml($icon, $label)
        );
    }

    /**
     * Hide delete button for disabled content elements
     *
     * @param array $row
     *
     * @return string
     */
    public function deleteButton($row)
    {
        if (\BackendUser::getInstance()->isAdmin || in_array($row['type'], $this->getAllowedElements(), true)) {
            return call_user_func_array(array(new tl_content(), 'deleteElement'), func_get_args());
        }

        return '';
    }

    /**
     * Hide toggle button for disabled content elements
     *
     * @param array $row
     *
     * @return string
     */
    public function toggleButton($row)
    {
        if (\BackendUser::getInstance()->isAdmin || in_array($row['type'], $this->getAllowedElements(), true)) {
            return call_user_func_array(array(new tl_content(), 'toggleIcon'), func_get_args());
        }

        return '';
    }

    public static function getContentParentTables()
    {
        static $tables;

        if (!is_array($tables)) {
            $tables = array();

            // Parse modules
            foreach ((array) $GLOBALS['BE_MOD'] as $modules) {
                foreach ((array) $modules as $moduleName => $moduleConfig) {
                    $moduleTables = (array) $moduleConfig['tables'];
                    // Skip modules without tl_content table
                    if (!\in_array('tl_content', $moduleTables, true)) {
                        continue;
                    }

                    $moduleGroup = $GLOBALS['TL_LANG']['MOD'][$moduleName][0];
                    $parentTables = [];

                    foreach ($moduleTables as $table) {
                        if ('tl_content' === $table) {
                            continue;
                        }

                        \Contao\Controller::loadDataContainer($table);

                        if (isset($GLOBALS['TL_DCA'][$table]['config']['ctable']) && \in_array('tl_content', (array) $GLOBALS['TL_DCA'][$table]['config']['ctable'])) {
                            $parentTables[] = $table;
                        }
                    }

                    $tableCount = \count($parentTables);
                    if (0 !== $tableCount) {
                        foreach ($parentTables as $table) {
                            $tables[$table] = $moduleGroup.($tableCount > 1 ? (' ('.$table.')') : '');
                        }
                    }
                }
            }
        }

        return $tables;
    }

    /**
     * Return all content elements as array
     *
     * @return array
     */
    public static function getContentElements()
    {
        static $elements;

        if (!is_array($elements)) {
            $elements = array();

            // Parse elements
            foreach ((array) $GLOBALS['TL_CTE'] as $elementGroup => $elementItems) {
                $groupLabel = $GLOBALS['TL_LANG']['CTE'][$elementGroup] ?? $elementGroup;

                foreach ((array) $elementItems as $element => $class) {
                    $elements[$groupLabel][$element] = $GLOBALS['TL_LANG']['CTE'][$element][0];
                }
            }
        }

        return $elements;
    }

    /**
     * Returns a list of allowed content element types.
     *
     * @return array
     */
    private function getAllowedElements()
    {
        $elements = array();

        /** @noinspection PhpUndefinedFieldInspection */
        foreach ((array) deserialize(\BackendUser::getInstance()->elements, true) as $item) {
            list($module, $element) = explode('.', $item, 2);

            if (\Input::get('do') === $module) {
                $elements[] = $element;
            }
        }

        return $elements;
    }
}
