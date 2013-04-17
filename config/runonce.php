<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2012 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  terminal42 gmbh 2009-2013
 * @author     Andreas Schempp <andreas.schempp@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html
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
