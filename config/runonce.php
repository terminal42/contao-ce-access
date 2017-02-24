<?php

/*
 * ce-access Extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2008-2017, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       http://github.com/terminal42/contao-ce-access
 */


class CeAccessRunonce extends Controller
{

    public function __construct()
    {
        parent::__construct();

        $this->import('Database');
    }


    /**
     * Convert negative-selection of column 'contentelements' in tl_user_group and tl_user to additive selection in the column 'elements'.
     */
    public static function run()
    {
        $objCeAccess = new self();

        $objCeAccess->invertElements('tl_user');
        $objCeAccess->invertElements('tl_user_group');
    }


    /**
     * Invert field logic for given table (tl_user or tl_user_group)
     * @param   string
     */
    private function invertElements($strTable)
    {
        if ($this->Database->fieldExists('contentelements', $strTable) && !$this->Database->fieldExists('elements', $strTable))
        {
            // Add the new field to the database table
            $this->Database->query("ALTER TABLE $strTable ADD COLUMN elements blob NULL");

            $objResult = $this->Database->execute("SELECT id, contentelements FROM $strTable WHERE contentelements!=''");

            while ($objResult->next())
            {
                $arrElements = deserialize($objResult->contentelements);

                if (!empty($arrElements) && is_array($arrElements)) {

                    $arrElements = array_diff(CeAccess::getContentElements(), $arrElements);

                    $this->Database->prepare("UPDATE $strTable SET elements=? WHERE id=?")->execute(serialize($arrElements), $objResult->id);
                }
            }

            // Delete old field to make sure the runonce is not executed again
            $this->Database->execute("ALTER TABLE $strTable DROP contentelements");

            $this->log('Inverted access logic for content elements in ' . $strTable, __METHOD__, TL_ACCESS);
        }
    }
}

CeAccessRunonce::run();
