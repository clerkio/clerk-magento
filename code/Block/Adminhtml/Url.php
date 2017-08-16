<?php

class Clerk_Clerk_Block_Adminhtml_Url extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Get store import URL
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $code = Mage::getSingleton('adminhtml/config_data')->getStore();
        $store = Mage::getModel('core/store')->load($code);
        $url = (string) $store->getBaseUrl().'clerk/api/store/'.$store->getId();

        return sprintf('<a href="%1$s" target="_blank">%1$s</a>', $url);
    }
}
