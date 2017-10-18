<?php

class Clerk_Clerk_Block_Adminhtml_Insights_Email extends Clerk_Clerk_Block_Adminhtml_Dashboard
{
    protected $type = 'email';

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return Mage::helper('clerk')->__('Clerk.io - Email Insights');
    }
}