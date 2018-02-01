<?php

class Clerk_Clerk_Block_Adminhtml_System_Config_Form_MultiselectAttributesField extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Only render if attributes are configured
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        if (Mage::getStoreConfig(Clerk_Clerk_Model_Config::XML_PATH_FACETED_SEARCH_ATTRIBUTES, $this->getStore())) {
            return parent::render($element);
        }

        return '';
    }

    /**
     * Get store being configured
     *
     * @return mixed
     */
    protected function getStore()
    {
        return Mage::getSingleton('adminhtml/config_data')->getStore();
    }
}