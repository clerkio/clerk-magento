<?php

require_once Mage::getModuleDir('controllers', 'Mage_CatalogSearch').DS.'ResultController.php';

class Clerk_Clerk_CatalogSearch_ResultController extends Mage_CatalogSearch_ResultController
{
    public function indexAction()
    {
        if (!Mage::helper('clerk')->getSetting('clerk/search/active')) {
            return parent::indexAction();
        }

        /* @var $query Mage_CatalogSearch_Model_Query */
        $query = Mage::helper('catalogsearch')->getQuery();
        $query->setStoreId(Mage::app()->getStore()->getId());

        //Save query
        if ($query->getQueryText() != '') {
            if (Mage::helper('catalogsearch')->isMinQueryLength()) {
                $query->setId(0)
                    ->setIsActive(1)
                    ->setIsProcessed(1);
            } else {
                if ($query->getId()) {
                    $query->setPopularity($query->getPopularity()+1);
                } else {
                    $query->setPopularity(1);
                }

                if ($query->getRedirect()) {
                    $query->save();
                    $this->getResponse()->setRedirect($query->getRedirect());
                    return;
                } else {
                    $query->prepare();
                }
            }

            Mage::helper('catalogsearch')->checkNotes();

            if (!Mage::helper('catalogsearch')->isMinQueryLength()) {
                $query->save();
            }
        }

        $this->getLayout()->getUpdate()->addUpdate('<remove name="search.result"/>');
        $this->getLayout()->getUpdate()->addUpdate('<remove name="catalogsearch.leftnav"/>');
        $this->getLayout()->getUpdate()->addUpdate('<remove name="enterprisesearch.leftnav"/>');
        $this->getLayout()->getUpdate()->addUpdate('<remove name="amshopby.navleft"/>');
        $this->loadLayout();

        $block = $this->getLayout()->createBlock('clerk/search');
        $block->setTemplate('clerk/search.phtml');
        $this->getLayout()->getBlock('content')->append($block);

        //Append faceted search filters if enabled
        $block = $this->getLayout()->createBlock('core/template');
        $block->setTemplate('clerk/facets.phtml');
        $this->getLayout()->getBlock('left')->append($block);

        $this->_initLayoutMessages('catalog/session');
        $this->_initLayoutMessages('checkout/session');
        $this->renderLayout();
    }

}
