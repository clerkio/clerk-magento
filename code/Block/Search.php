<?php

class Clerk_Clerk_Block_Search extends Mage_CatalogSearch_Block_Result
{
    
    const XML_PATH_CLERK_SEARCH_SHOW_CATEGORIES = 'clerk/search/show_categories';
    const XML_PATH_CLERK_SEARCH_CATEGORIES = 'clerk/search/categories';
    const XML_PATH_CLERK_SEARCH_PAGES = 'clerk/search/pages';
    const XML_PATH_CLERK_SEARCH_PAGES_TYPE = 'clerk/search/pages-type';


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

        if(Mage::getStoreConfig(self::XML_PATH_CLERK_SEARCH_SHOW_CATEGORIES)){

            $spanAttributes['data-search-categories'] = $this->getCategories();
            $spanAttributes['data-search-pages'] = $this->getPages();
            $spanAttributes['data-search-pages-type'] = $this->getPagesType();

        }


        if (Mage::getStoreConfigFlag(Clerk_Clerk_Model_Config::XML_PATH_FACETED_SEARCH_ENABLED)) {
            $spanAttributes['data-facets-target'] = "#clerk-search-filters";
            $spanAttributes['data-facets-design'] = $this->getFacetsDesign();

            if ($titles = Mage::getStoreConfig(Clerk_Clerk_Model_Config::XML_PATH_FACETED_SEARCH_TITLES)) {
                $titles = json_decode($titles, true);

                // sort by sort_order
                uasort($titles, function($a, $b) {
                    return $a['sort_order'] > $b['sort_order'];
                });

                $labels = [];

                foreach ($titles as $title) {

                    array_push($labels, $title['label']);

                }

                $spanAttributes['data-facets-titles'] = json_encode(array_filter(array_combine(array_keys($titles), $labels)));
                $spanAttributes['data-facets-attributes'] = json_encode(array_keys($titles));

                if ($multiselectAttributes = Mage::getStoreConfig(Clerk_Clerk_Model_Config::XML_PATH_FACETED_SEARCH_MULTISELECT_ATTRIBUTES)) {
                    $spanAttributes['data-facets-multiselect-attributes'] = '["' . str_replace(',', '","', $multiselectAttributes) . '"]';
                }
            }
        }


        foreach ($spanAttributes as $attribute => $value) {
            $output .= ' ' . $attribute . '=\'' . htmlspecialchars($value, ENT_QUOTES) . '\'';
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
     * Get categories
     *
     * @return mixed
     */
    public function getCategories()
    {
        return Mage::getStoreConfig(self::XML_PATH_CLERK_SEARCH_CATEGORIES);
    }

    /**
     * Get pages
     *
     * @return mixed
     */
    public function getPages()
    {
        return Mage::getStoreConfig(self::XML_PATH_CLERK_SEARCH_PAGES);
    }

    /**
     * Get pages-type
     *
     * @return mixed
     */
    public function getPagesType()
    {
        return Mage::getStoreConfig(self::XML_PATH_CLERK_SEARCH_PAGES_TYPE);
    }

     /**
     * Get Facet design
     *
     * @return mixed
     */
    public function getFacetsDesign()
    {
        return Mage::getStoreConfig(Clerk_Clerk_Model_Config::XML_PATH_FACETED_SEARCH_DESIGN);
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
