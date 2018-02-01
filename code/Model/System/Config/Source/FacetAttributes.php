<?php

class Clerk_Clerk_Model_System_Config_Source_FacetAttributes
{
    /**
     * Get facet attribute options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $attributes = $this->getFacetAttributes();

        $values = [];

        foreach ($attributes->facets as $attribute => $facet) {
            $values[] = [
                'value' => $attribute,
                'label' => $attribute,
            ];
        }

        return $values;
    }

    /**
     * Get facet attributes from API
     *
     * @return mixed
     */
    protected function getFacetAttributes()
    {
        $attributes = Mage::getModel('clerk/communicator')->getFacetAttributes($this->getStore());

        if ($attributes) {
            return json_decode($attributes->getBody());
        }

        return $attributes;
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