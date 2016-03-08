<?php

class Clerk_Clerk_Block_Adminhtml_Url extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $code = Mage::getSingleton('adminhtml/config_data')->getStore();
        $code = Mage::getModel('core/store')->load($code)->getId();
        $url = (string) Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB).'clerk/api/store/'.$code;

        return "<a href=\"{$url}\">{$url}</a>";
    }
}
