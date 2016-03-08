<?php

class Clerk_Clerk_Model_Adminhtml_Powertype
{
    public function toOptionArray()
    {
        $power_types = array();

        $power_types[] = array(
            'value' => 'page',
            'label' => Mage::helper('clerk')->__('Page'),
        );

        $power_types[] = array(
            'value' => 'popup',
            'label' => Mage::helper('clerk')->__('Popup'),
        );

        return $power_types;
    }
}
