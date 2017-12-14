<?php

class Clerk_Clerk_Block_ExitIntent extends Mage_Core_Block_Template
{
    const XML_PATH_EXIT_INTENT_ENABLED = 'clerk/exit_intent/active';
    const XML_PATH_EXIT_INTENT_TEMPLATE = 'clerk/exit_intent/template';

    /**
     * Determine if we should show exit intent
     *
     * @return bool
     */
    public function shouldShow()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_EXIT_INTENT_ENABLED);
    }

    /**
     * Get exit intent template
     *
     * @return mixed
     */
    public function getExitIntentTemplate()
    {
        return Mage::getStoreConfig(self::XML_PATH_EXIT_INTENT_TEMPLATE);
    }
}