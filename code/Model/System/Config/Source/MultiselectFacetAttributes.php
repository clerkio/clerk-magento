<?php

class Clerk_Clerk_Model_System_Config_Source_MultiselectFacetAttributes
{
    /**
     * Get multiselectable facet attribute
     *
     * @return array
     */
    public function toOptionArray()
    {
        $attributes = $this->getConfiguredAttributes();

        $values = [];

        foreach (explode(',', $attributes) as $attribute) {
            $values[] = [
                'value' => $attribute,
                'label' => $attribute,
            ];
        }

        return $values;
    }

    /**
     * Get configured facet attributes
     *
     * @return mixed
     */
    protected function getConfiguredAttributes()
    {
        return Mage::getStoreConfig(Clerk_Clerk_Model_Config::XML_PATH_FACETED_SEARCH_ATTRIBUTES, $this->getStore());
    }

    /**
     * Get store being configured
     *
     * @return mixed
     */
    protected function getStore()
    {
        return Mage::getSingleton('adminhtml/config_data')->getStore();
    }
}