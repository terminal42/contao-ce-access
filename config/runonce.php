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

    public function run()
    {
        $this->import('Database');

        $arrElements = array();
        foreach ($GLOBALS['TL_CTE'] as $k=>$v)
        {
            foreach (array_keys($v) as $kk)
            {
                $arrElements[] = $kk;
            }
        }

        // update database
        // convert negative-selection of column 'contentelements' in tl_user_group and tl_user to additive selection in the column 'elements'.

        if( $this->Database->fieldExists('contentelements', 'tl_user') )
        {
            if( !$this->Database->fieldExists('elements', 'tl_user') )
            {
                $this->Database->query("ALTER TABLE tl_user ADD COLUMN elements blob NULL");
            }

            $objElements = $this->Database->execute("SELECT * FROM tl_user WHERE contentelements!=''");
            $arrAllowedElements = array();

            while( $objElements->next() )
            {
                $arrContentElements = deserialize($objElements->contentelements);
                if (is_array($arrContentElements))
                {
                    $arrAllowedElements[$objElements->id] = array();
                    foreach($arrElements as $strElement)
                    {
                        if( !in_array($strElement, $arrContentElements) )
                        {
                            $arrAllowedElements[$objElements->id][] = $strElement;
                        }
                    }
                    $strElements = serialize($arrAllowedElements[$objElements->id]);
                    $objUpdate = $this->Database->prepare("UPDATE tl_user SET elements=? WHERE id=? ")->execute($strElements, $objElements->id);
                }
            }

            $this->Database->execute("ALTER TABLE tl_user DROP contentelements");
        }


        if( $this->Database->fieldExists('contentelements', 'tl_user_group') )
        {
            if( !$this->Database->fieldExists('elements', 'tl_user_group') )
            {
                $this->Database->query("ALTER TABLE tl_user_group ADD COLUMN elements blob NULL");
            }

            $objElements = $this->Database->execute("SELECT * FROM tl_user_group WHERE contentelements!=''");
            $arrAllowedGroupElements = array();

            while( $objElements->next() )
            {
                $arrContentGroupElements = deserialize($objElements->contentelements);
                if (is_array($arrContentGroupElements))
                {
                    $arrAllowedGroupElements[$objElements->id] = array();
                    foreach($arrElements as $strElement)
                    {
                        if( !in_array($strElement, $arrContentGroupElements) )
                        {
                            $arrAllowedGroupElements[$objElements->id][] = $strElement;
                        }
                    }

                    $strGroupElements = serialize($arrAllowedGroupElements[$objElements->id]);
                    $objUpdateGroup = $this->Database->prepare("UPDATE tl_user_group SET elements=? WHERE id=?")->execute($strGroupElements, $objElements->id);
                }
            }

            $this->Database->execute("ALTER TABLE tl_user_group DROP contentelements");

        }


    }
}

$objRunonce = new CeAccessRunonce();
$objRunonce->run();
