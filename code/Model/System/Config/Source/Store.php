<?php

class Clerk_Clerk_Model_System_Config_Source_Store extends Mage_Adminhtml_Model_System_Config_Source_Store
{
    /**
     * @var array
     */
    protected $_options = [];

    /**
     * Get available stores
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->_options) {
            $this->_options = Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(true, false);
            $this->_options[0]['label'] = Mage::helper('clerk')->__('-- Please Select --');
        }

        return $this->_options;
    }
}