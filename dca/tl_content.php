<?php

/*
 * ce-access Extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2008-2017, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       http://github.com/terminal42/contao-ce-access
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
