<?php

class Clerk_Clerk_Model_System_Config_Source_Onlyvisibility
{
    /**
     * Get visibility types
     *
     * @return array
     */

    
    public function toOptionArray()
    {
        $pv= array(
            array(
                'value' => '2',
                'label' => Mage::helper('clerk')->__('Catalog'),
            ),
            array(
                'value' => '3',
                'label' => Mage::helper('clerk')->__('Search'),
            ),
            array(
                'value' => '4',
                'label' => Mage::helper('clerk')->__('Catalog, Search'),
            ),
            array(
                'value' => '0',
                'label' => Mage::helper('clerk')->__('All Above'),
            ),

        );

        return $pv;
    }
    
}
