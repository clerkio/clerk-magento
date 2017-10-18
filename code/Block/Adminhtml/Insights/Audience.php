<?php

class Clerk_Clerk_Block_Adminhtml_Insights_Audience extends Clerk_Clerk_Block_Adminhtml_Dashboard
{
    protected $type = 'audience';

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return Mage::helper('clerk')->__('Clerk.io - Audience Insights');
    }
}