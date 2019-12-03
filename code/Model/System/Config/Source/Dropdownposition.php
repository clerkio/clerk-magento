<?php

class Clerk_Clerk_Model_System_Config_Source_Dropdownposition
{
    /**
     * Get powerstep types
     *
     * @return array
     */
    public function toOptionArray()
    {
        $positions = array(
            array(
                'value' => 'left',
                'label' => Mage::helper('clerk')->__('Left'),
            ),
            array(
                'value' => 'center',
                'label' => Mage::helper('clerk')->__('Center'),
            ),
            array(
                'value' => 'right',
                'label' => Mage::helper('clerk')->__('Right'),
            ),
            array(
                'value' => 'below',
                'label' => Mage::helper('clerk')->__('Below'),
            ),
            array(
                'value' => 'off',
                'label' => Mage::helper('clerk')->__('Off'),
            )
        );

        return $positions;
    }
}
