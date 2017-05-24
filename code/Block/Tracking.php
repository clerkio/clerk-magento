<?php

class Clerk_Clerk_Block_Tracking extends Mage_Core_Block_Template
{
    const XML_PATH_PUBLIC_KEY = 'clerk/general/publicapikey';

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
}