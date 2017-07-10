<?php

/*
 * ce-access Extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2008-2017, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       http://github.com/terminal42/contao-ce-access
 */

class CeAccessRunonce
{
    /**
     * Convert negative-selection of column 'contentelements' in tl_user_group and tl_user to additive selection in the column 'elements'.
     */
    public static function run()
    {
        $objCeAccess = new self();

        $objCeAccess->invertElements('tl_user');
        $objCeAccess->invertElements('tl_user_group');

        $objCeAccess->groupElements('tl_user');
        $objCeAccess->groupElements('tl_user_group');
    }

    /**
     * Invert field logic for given table (tl_user or tl_user_group).
     *
     * @param string $strTable
     */
    private function invertElements($strTable)
    {
        $db = \Database::getInstance();

        if ($db->fieldExists('contentelements', $strTable) && !$db->fieldExists('elements', $strTable)) {
            // Add the new field to the database table
            $db->query("ALTER TABLE $strTable ADD COLUMN elements blob NULL");

            $objResult = $db->execute("SELECT id, contentelements FROM $strTable WHERE contentelements!=''");

            while ($objResult->next()) {
                $arrElements = deserialize($objResult->contentelements);

                if (!empty($arrElements) && is_array($arrElements)) {
                    $arrElements = array_diff(CeAccess::getContentElements(), $arrElements);

                    $db
                        ->prepare("UPDATE $strTable SET elements=? WHERE id=?")
                        ->execute(serialize($arrElements), $objResult->id)
                    ;
                }
            }

            // Delete old field to make sure the runonce is not executed again
            $db->execute("ALTER TABLE $strTable DROP contentelements");

            \System::log('Inverted access logic for content elements in '.$strTable, __METHOD__, TL_ACCESS);
        }
    }

    /**
     * Group records from the old format "text" to new "article.text"
     *
     * @param string $table
     */
    private function groupElements($table)
    {
        $records = \Database::getInstance()->execute("SELECT id, elements FROM $table");

        while ($records->next()) {
            $elements = deserialize($records->elements, true);

            // The format is already correct
            if (empty($elements) || strpos($elements[0], '.') !== false) {
                continue;
            }

            $modules = array();

            // Gather the modules
            foreach ($GLOBALS['BE_MOD'] as $moduleConfigs) {
                foreach ($moduleConfigs as $moduleName => $moduleConfig) {

                    // Skip modules without tl_content table
                    if (!in_array('tl_content', (array) $moduleConfig['tables'])) {
                        continue;
                    }

                    $modules[] = $moduleName;
                }
            }

            // Update the elements
            foreach ($elements as $key => $element) {
                foreach ($modules as $module) {
                    $elements[] = $module . '.' . $element;
                }

                unset($elements[$key]);
            }

            \Database::getInstance()->prepare("UPDATE $table SET elements=? WHERE id=?")
                ->execute(serialize($elements), $records->id);
        }

        \System::log('Grouped the content elements in ' . $table, __METHOD__, TL_ACCESS);
    }
}

CeAccessRunonce::run();
