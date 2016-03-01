<?php

class Clerk_Clerk_Model_Adminhtml_System_Config_Source_PowerType
{
    public function toOptionArray()
    {
        $power_types = array();

        $power_types[] = array(
                        'value' => 'landingpage',
                        'label' => Mage::helper('clerk')->__('Power Step Page'),
                    );

        $power_types[] = array(
                        'value' => 'popup',
                        'label' => Mage::helper('clerk')->__('Power Step Popup'),
                    );

        return $power_types;
    }
}
