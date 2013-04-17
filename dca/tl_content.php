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


/**
 * Configuration
 */
$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = array('CeAccess', 'filterContentElements');


/**
 * Operations
 */
$GLOBALS['TL_DCA']['tl_content']['list']['operations']['edit']['button_callback']   = array('CeAccess', 'hideButton');
$GLOBALS['TL_DCA']['tl_content']['list']['operations']['copy']['button_callback']   = array('CeAccess', 'hideButton');
$GLOBALS['TL_DCA']['tl_content']['list']['operations']['cut']['button_callback']    = array('CeAccess', 'hideButton');
$GLOBALS['TL_DCA']['tl_content']['list']['operations']['delete']['button_callback'] = array('CeAccess', 'deleteButton');
$GLOBALS['TL_DCA']['tl_content']['list']['operations']['toggle']['button_callback'] = array('CeAccess', 'toggleButton');
