<?php

class Clerk_Clerk_Model_System_Config_Source_Content
{
    /**
     * Get content
     *
     * @return array
     */
    public function toOptionArray()
    {
        $code = Mage::getSingleton('adminhtml/config_data')->getStore();
        $store = Mage::getModel('core/store')->load($code);
        $content = Mage::getModel('clerk/api')->getContent($store);

        if ($content) {
            return $content;
        }

        return array(
            array(
                'value' => '',
                'label' => 'Add keys first'
            )
        );
    }
}
