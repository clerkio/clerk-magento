<?php

class Clerk_Clerk_Block_Tracking extends Mage_Core_Block_Template
{
    public const XML_PATH_PUBLIC_KEY = 'clerk/general/publicapikey';
    public const XML_PATH_COLLECT_EMAILS = 'clerk/general/collect_emails';

    /**
     * Get public key
     *
     * @return mixed
     */
    public function getPublicKey()
    {
        return Mage::getStoreConfig(self::XML_PATH_PUBLIC_KEY);
    }

    /**
     * Get form key
     *
     * @return mixed
     */
    public function getFormKey()
    {
        return Mage::getSingleton('core/session')->getFormKey();
    }

    /**
     * Determine if we should collect emails
     *
     * @return bool
     */
    public function collectEmails()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_COLLECT_EMAILS);
    }

    /**
     * Get store name
     */
    public function getStoreName()
    {
        return Mage::app()->getStore()->getName();
    }

    public function getClerkJSUrl()
    {
        $storeName = $this->getStoreName();
        $storeName = preg_replace('/[^a-z]/', '', strtolower($storeName));
        return '://custom.clerk.io/' . $storeName . '.js';
    }
}
