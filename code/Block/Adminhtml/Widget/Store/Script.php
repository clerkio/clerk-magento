<?php

class Clerk_Clerk_Block_Adminhtml_Widget_Store_Script extends Mage_Adminhtml_Block_Template
{
    /**
     * Unique identifier for block that uses Widget
     *
     * @return string
     */
    public function getUniqId()
    {
        return $this->_getData('uniq_id');
    }

    /**
     * Widget ajax URL getter
     *
     * @return string
     */
    public function getSourceUrl()
    {
        return $this->_getData('ajax_url');
    }

    /**
     * Return chooser HTML and init scripts
     *
     * @return string
     */
    protected function _toHtml()
    {
        $element   = $this->getElement();
        /* @var $fieldset Varien_Data_Form_Element_Fieldset */
        $fieldset  = $element->getForm()->getElement($this->getFieldsetId());
        $config    = $this->getConfig();
        $selectId = $element->getId();


        return '
<script>
(function() {
    var instantiateClerkWidget = function() {
        window.clerkWidget = new WysiwygWidget.ClerkWidget("' . $selectId . '", "' . $this->getContentUrl() . '", "' . $this->getParametersUrl() . '");
    }

    if (document.loaded) { //allow load over ajax
        instantiateClerkWidget();
    } else {
        document.observe("dom:loaded", instantiateClerkWidget);
    }
})();
</script>'.$clerk_confirm;
    }
}