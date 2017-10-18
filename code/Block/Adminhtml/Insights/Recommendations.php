<?php

class Clerk_Clerk_Block_Adminhtml_Insights_Recommendations extends Clerk_Clerk_Block_Adminhtml_Dashboard
{
    protected $type = 'recommendations';

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return Mage::helper('clerk')->__('Clerk.io - Recommendations Insights');
    }
}