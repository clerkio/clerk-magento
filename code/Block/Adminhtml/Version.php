<?php

class Clerk_Clerk_Block_Adminhtml_Version extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return (string) Mage::getConfig()->getNode()->modules->Clerk_Clerk->version;
    }
}
