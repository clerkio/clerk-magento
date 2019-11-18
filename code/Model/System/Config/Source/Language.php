<?php

class Clerk_Clerk_Model_System_Config_Source_Language
{
    /**
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     */
    public function toOptionArray()
    {
        $Langs = array(
            array(
                'value' => 'danish',
                'label' => Mage::helper('clerk')->__('Danish'),
            ),
            array(
                'value' => 'dutch',
                'label' => Mage::helper('clerk')->__('Dutch'),
            ),
            array(
                'value' => 'english',
                'label' => Mage::helper('clerk')->__('English'),
            ),
            array(
                'value' => 'finnish',
                'label' => Mage::helper('clerk')->__('Finnish'),
            ),
            array(
                'value' => 'french',
                'label' => Mage::helper('clerk')->__('French'),
            ),
            array(
                'value' => 'german',
                'label' => Mage::helper('clerk')->__('German'),
            ),
            array(
                'value' => 'hungarian',
                'label' => Mage::helper('clerk')->__('Hungarian'),
            ),
            array(
                'value' => 'italian',
                'label' => Mage::helper('clerk')->__('Italian'),
            ),
            array(
                'value' => 'norwegian',
                'label' => Mage::helper('clerk')->__('Norwegian'),
            ),
            array(
                'value' => 'portuguese',
                'label' => Mage::helper('clerk')->__('Portuguese'),
            ),
            array(
                'value' => 'romanian',
                'label' => Mage::helper('clerk')->__('Romanian'),
            ),
            array(
                'value' => 'russian',
                'label' => Mage::helper('clerk')->__('Russian'),
            ),
            array(
                'value' => 'spanish',
                'label' => Mage::helper('clerk')->__('Spanish'),
            ),
            array(
                'value' => 'swedish',
                'label' => Mage::helper('clerk')->__('Swedish'),
            ),
            array(
                'value' => 'turkish',
                'label' => Mage::helper('clerk')->__('Turkish'),
            ),
        );

        $locale = Mage::getStoreConfig('general/locale/code', Mage::app()->getStore()->getId());

        $LangsAuto = [
            'da_DK' => 'Danish',
            'nl_NL' => 'Dutch',
            'en_US' => 'English',
            'en_GB' => 'English',
            'fi' => 'Finnish',
            'fr_FR' => 'French',
            'fr_BE' => 'French',
            'de_DE' => 'German',
            'hu_HU' => 'Hungarian',
            'it_IT' => 'Italian',
            'nn_NO' => 'Norwegian',
            'nb_NO' => 'Norwegian',
            'pt_PT' => 'Portuguese',
            'pt_BR' => 'Portuguese',
            'ro_RO' => 'Romanian',
            'ru_RU' => 'Russian',
            'ru_UA' => 'Russian',
            'es_ES' => 'Spanish',
            'sv_SE' => 'Swedish',
            'tr_TR' => 'Turkish'
        ];

        if (isset($LangsAuto[$locale])) {

            $AutoLang = ['label' => sprintf( 'Auto (%s)', $LangsAuto[$locale]), 'value' => 'auto_'.strtolower($LangsAuto[$locale])];

        }

        if (isset($AutoLang)) {

            array_unshift($Langs, $AutoLang);

        }

        return $Langs;
    }
}
