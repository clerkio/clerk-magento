<?php

class Clerk_Clerk_Block_Search extends Mage_CatalogSearch_Block_Result
{
    const XML_PATH_CLERK_NO_RESULTS_TEXT = 'clerk/search/no_results_text';
    const XML_PATH_CLERK_LOAD_MORE_TEXT = 'clerk/search/load_more_text';
    const TARGET_ID = 'clerk-search-results';

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
     * Get attributes for clerk span
     *
     * @return string
     * @throws Exception
     */
    public function getSpanAttributes()
    {
        $output = '';
        $spanAttributes = [
            'id' => 'clerk-search',
            'class' => 'clerk',
            'data-template' => '@' . $this->getClerkTemplate(),
            'data-offset' => '0',
            'data-target' => '#' . $this->getTargetId(),
            'data-after-render' => '_clerk_after_load_event',
            'data-query' => $this->getRequest()->getParam('q'),
        ];

        if (Mage::getStoreConfigFlag(Clerk_Clerk_Model_Config::XML_PATH_FACETED_SEARCH_ENABLED)) {
            if ($attributes = Mage::getStoreConfig(Clerk_Clerk_Model_Config::XML_PATH_FACETED_SEARCH_ATTRIBUTES)) {
                $spanAttributes['data-facets-target'] = "#clerk-search-filters";
                $spanAttributes['data-facets-attributes'] = '["' . str_replace(',', '","', $attributes) . '"]';

                if ($multiselectAttributes = Mage::getStoreConfig(Clerk_Clerk_Model_Config::XML_PATH_FACETED_SEARCH_MULTISELECT_ATTRIBUTES)) {
                    $spanAttributes['data-facets-multiselect-attributes'] = '["' . str_replace(',', '","', $multiselectAttributes) . '"]';
                }

                if ($titles = Mage::getStoreConfig(Clerk_Clerk_Model_Config::XML_PATH_FACETED_SEARCH_TITLES)) {
                    $spanAttributes['data-facets-titles'] = $titles;
                }
            }
        }


        foreach ($spanAttributes as $attribute => $value) {
            $output .= ' ' . $attribute . '=\'' . $value . '\'';
        }

        return trim($output);
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

    /**
     * Get search page content
     *
     * @return string
     */
    protected function getClerkTemplate()
    {
        $template = Mage::helper('clerk')->getSetting('clerk/search/template');
        return ltrim($template, '@');
    }

    /**
     * Get html id of target
     *
     * @return string
     */
    public function getTargetId()
    {
        return self::TARGET_ID;
    }
}
