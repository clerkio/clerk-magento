<?php

require_once Mage::getModuleDir('controllers', 'Mage_CatalogSearch').DS.'ResultController.php';

class Clerk_Clerk_CatalogSearch_ResultController extends Mage_CatalogSearch_ResultController
{
    public function indexAction()
    {
        if (!Mage::helper('clerk')->getSetting('clerk/search/active')) {
            return parent::indexAction();
        }
        $this->getLayout()->getUpdate()->addUpdate('<remove name="search.result"/>');
        $this->getLayout()->getUpdate()->addUpdate('<remove name="catalogsearch.leftnav"/>');
        $this->getLayout()->getUpdate()->addUpdate('<remove name="enterprisesearch.leftnav"/>');
        $this->getLayout()->getUpdate()->addUpdate('<remove name="amshopby.navleft"/>');
        $this->loadLayout();
        // $block = $this->getLayout()->createBlock('catalogsearch/result');
        $block = $this->getLayout()->createBlock('clerk/search');
        $block->setTemplate('clerk/search.phtml');
        $this->getLayout()->getBlock('content')->append($block);
        $this->_initLayoutMessages('catalog/session');
        $this->_initLayoutMessages('checkout/session');
        $this->renderLayout();
    }
}
