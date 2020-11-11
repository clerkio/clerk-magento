<?php

class Clerk_Clerk_Model_System_Config_Source_Pagestype
{
    /**
     * Get powerstep types
     *
     * @return array
     */
    public function toOptionArray()
    {
        $pages_types = array(
            array(
                'value' => 'cms page',
                'label' => Mage::helper('clerk')->__('CMS Page'),
            ),
        );

        return $pages_types;
    }
}
