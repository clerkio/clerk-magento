<?php

class Clerk_Clerk_Block_Adminhtml_Dashboard extends Mage_Adminhtml_Block_Template
{
    const XML_PATH_ENABLED = 'clerk/general/active';
    const XML_PATH_PUBLIC_KEY = 'clerk/general/publicapikey';
    const XML_PATH_PRIVATE_KEY = 'clerk/general/privateapikey';

    protected $type = 'dashboard';

    /**
     * Get iframe embed url
     *
     * @return string
     */
    public function getEmbedUrl()
    {
        if (! $this->getStoreId()) {
            return false;
        }

        if (! Mage::getStoreConfigFlag(self::XML_PATH_ENABLED, $this->getStoreId())) {
            return false;
        }

        $publicKey = Mage::getStoreConfig(self::XML_PATH_PUBLIC_KEY, $this->getStoreId());
        $privateKey = Mage::getStoreConfig(self::XML_PATH_PRIVATE_KEY, $this->getStoreId());
        $storePart = $this->getStorePart($publicKey);

        return sprintf('https://my.clerk.io/#/store/%s/analytics/%s?key=%s&private_key=%s&embed=yes', $storePart, $this->type, $publicKey, $privateKey);
    }

    /**
     * Get first 8 characters of public key
     *
     * @param $publicKey
     * @return string
     */
    protected function getStorePart($publicKey)
    {
        return substr($publicKey, 0, 8);
    }

    /**
     * Get dashboard title
     *
     * @return string
     */
    public function getTitle()
    {
        return Mage::helper('clerk')->__('Clerk.io Dashboard');
    }

    /**
     * Get current store
     *
     * @return Mage_Core_Model_Abstract
     */
    protected function getStore()
    {
        return Mage::getModel('core/store')->load($this->getStoreId());
    }

    /**
     * Get website for store
     *
     * @return mixed
     */
    protected function getWebsite()
    {
        return $this->getStore()->getWebsite();
    }

    /**
     * Get store id
     *
     * @return mixed
     */
    public function getStoreId()
    {
        return $this->getRequest()->getParam('store') ? $this->getRequest()->getParam('store') : Mage::app()->getDefaultStoreView()->getId();
    }

    /**
     * Get url to configure store for website
     *
     * @return string
     */
    public function getConfigureUrl()
    {
        return $this->getUrl('adminhtml/system_config/edit/section/clerk', array('website' => $this->getWebsite()->getCode(), 'store' => $this->getStore()->getCode()));
    }
}
