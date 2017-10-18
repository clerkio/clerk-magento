<?php

class Clerk_Clerk_Block_Adminhtml_Insights_Search extends Clerk_Clerk_Block_Adminhtml_Dashboard
{
    protected $type = 'search';

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return Mage::helper('clerk')->__('Clerk.io - Search Insights');
    }
}