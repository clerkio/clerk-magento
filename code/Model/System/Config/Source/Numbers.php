<?php

class Clerk_Clerk_Model_System_Config_Source_Numbers
{
    /**
     * Get powerstep types
     *
     * @return array
     */
    public function toOptionArray()
    {
        $numbers = array(
            array(
                'value' => '1',
                'label' => Mage::helper('clerk')->__('1'),
            ),
            array(
                'value' => '2',
                'label' => Mage::helper('clerk')->__('2'),
            ),
            array(
                'value' => '3',
                'label' => Mage::helper('clerk')->__('3'),
            ),
            array(
                'value' => '4',
                'label' => Mage::helper('clerk')->__('4'),
            ),
            array(
                'value' => '5',
                'label' => Mage::helper('clerk')->__('5'),
            ),
            array(
                'value' => '6',
                'label' => Mage::helper('clerk')->__('6'),
            ),
            array(
                'value' => '7',
                'label' => Mage::helper('clerk')->__('7'),
            ),
            array(
                'value' => '8',
                'label' => Mage::helper('clerk')->__('8'),
            ),
            array(
                'value' => '9',
                'label' => Mage::helper('clerk')->__('9'),
            ),
            array(
                'value' => '10',
                'label' => Mage::helper('clerk')->__('10'),
            ),
        );

        return $numbers;
    }
}
