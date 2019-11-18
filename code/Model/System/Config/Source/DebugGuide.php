<?php


class Clerk_Clerk_Model_System_Config_Source_DebugGuide
{

    /**
     * Get powerstep types
     *
     * @return array
     */
    public function toOptionArray()
    {
        $debug_guide = '<hr><strong>PrestaShop Debug Mode is disabled</strong>'.
                '<p>When debug mode is disabled, PrestaShop hides a lot of errors and making it impossible for Clerk logger to detect and catch these errors.</p>'.
                '<p>To make it possibel for Clerk logger to catch all errors you have to enable debug mode.</p>'.
                '<p>Debug is not recommended in production in a longer period of time.</p>'.
                '</br><p><strong>When you store is in debug mode</strong></p>'.
                '<ul>'.
                '<li>Caching is disabled.</li>'.
                '<li>Errors will be visible.</li>'.
                '<li>Clerk logger can catch all errors.</li>'.
                '</ul>'.
                '</br><p><strong>Step By Step Guide to enable debug mode</strong></p>'.
                '<ol>'.
                '<li>Please enable PrestaShop Debug Mode.</li>'.
                '<li>Enable Clerk Logging.</li>'.
                '<li>Set the logging level to "ERROR + WARN + DEBUG".</li>'.
                '<li>Set Logging to "my.clerk.io".</li>'.
                '</ol>'.
                '<p>Thanks, that will make it a lot easier for our customer support to help you.</p>'.
                '</br><p><strong>HOW TO ENABLE DEBUG MODE:</strong></p>'.
                '<p>Open config/defines.inc.php and usually at line 29 you will find</p>'.
                '<p>define(\'_PS_MODE_DEV_\', false);</p>'.
                '<p>change it to:</p>'.
                '<p>define(\'_PS_MODE_DEV_\', true);</p>'.
                '<hr>';

        return $debug_guide;
    }

}