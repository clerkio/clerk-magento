<?php

class Clerk_Clerk_Block_Search extends Mage_CatalogSearch_Block_Result
{
    const XML_PATH_CLERK_NO_RESULTS_TEXT = 'clerk/search/no_results_text';
    const XML_PATH_CLERK_LOAD_MORE_TEXT = 'clerk/search/load_more_text';

    protected function _construct()
    {
        $template = Mage::helper('clerk')->getSetting('clerk/search/template');
        $this->template = ltrim($template, '@');
    }

    /**
     * Get title text
     *
     * @return string
     */
    public function getTitleText()
    {
        return $this->__(
            "Search results for '%s'",
            $this->helper('catalogsearch')->getEscapedQueryText()
        );
    }

    /**
     * Get no results text
     *
     * @return mixed
     */
    public function getNoResultsText()
    {
        return Mage::getStoreConfig(self::XML_PATH_CLERK_NO_RESULTS_TEXT);
    }

    /**
     * Get load more text
     *
     * @return mixed
     */
    public function getLoadMoreText()
    {
        return Mage::getStoreConfig(self::XML_PATH_CLERK_LOAD_MORE_TEXT);
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setTemplate('clerk/search.phtml');
    }
}
