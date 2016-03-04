<?php

class Clerk_Clerk_Block_Search extends Mage_CatalogSearch_Block_Result
{
    protected function _construct()
    {
        $this->noResultsText = Mage::helper('clerk')->getSetting('clerk/search/no_results_text');
        $this->loadMoreText = Mage::helper('clerk')->getSetting('clerk/search/load_more_text');
        $this->titleText = $this->__(
            "Search results for '%s'",
            $this->helper('catalogsearch')->getEscapedQueryText()
        );
        $template = Mage::helper('clerk')->getSetting('clerk/search/template');
        $this->template = ltrim($template, '@');
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setTemplate('clerk/search.phtml');
    }
}
