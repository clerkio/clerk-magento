<?php

class Clerk_Clerk_Model_System_Config_Source_Powertype
{
    /**
     * Get powerstep types
     *
     * @return array
     */
    public function toOptionArray()
    {
        $power_types = array(
            array(
                'value' => 'page',
                'label' => Mage::helper('clerk')->__('Page'),
            ),
            array(
                'value' => 'popup',
                'label' => Mage::helper('clerk')->__('Popup'),
            ),
        );

        return $power_types;
    }
}
