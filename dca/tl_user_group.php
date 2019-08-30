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
 * Palettes
 */
\Contao\CoreBundle\DataContainer\PaletteManipulator::create()
    ->addLegend('elements_legend', 'modules_legend', \Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_BEFORE)
    ->addField('elements', 'elements_legend', \Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_user_group')
;

/**
 * Fields
 */
$GLOBALS['TL_DCA']['tl_user_group']['fields']['elements'] = array
(
    'exclude'                 => true,
    'input_field_callback'    => function(\Contao\DataContainer $dc) {
        $buffer = '';
        $submitted = false;
        $hasError = false;
        $value = \Contao\StringUtil::deserialize($dc->value, true);

        $elements = CeAccess::getContentElements();

        foreach (CeAccess::getContentParentTables() as $parentTable => $label) {
            $currentValue = $value[$parentTable] ?? [];
            $tableElements = [];

            foreach ($elements as $group => $groupElements) {
                foreach ($groupElements as $elementName => $elementLabel) {
                    $tableElements[$group][$parentTable.'.'.$elementName] = $elementLabel;
                }
            }

            $widget = new \Contao\CheckBox(\Contao\CheckBox::getAttributesFromDca([
                'label' => [sprintf($GLOBALS['TL_LANG']['tl_user_group']['elements'][0], $label), $GLOBALS['TL_LANG']['tl_user_group']['elements'][1]],
                'options' => $tableElements,
                'eval' => ['multiple' => true],
            ], $dc->inputName.'_'.$parentTable, $currentValue, $dc->inputName, $dc->table, $dc));

            if (isset($_POST[$widget->name])) {
                $widget->validate();

                if ($widget->hasErrors()) {
                    $hasError = true;
                } else {
                    $value[$parentTable] = $widget->value ?: [];
                    $submitted = true;
                }
            }

            $buffer .= $widget->parse();
        }

        if ($submitted && !$hasError) {
            dump($value);
        }

        return '<div class="widget">'.$buffer.'</div>';
    },
);
