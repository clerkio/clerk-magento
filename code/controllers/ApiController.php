<?php
require_once(Mage::getBaseDir('code') . '/community/Clerk/Clerk/controllers/ClerkLogger.php');

class Clerk_Clerk_ApiController extends Mage_Core_Controller_Front_Action
{
    /**
     *
     */
    const XML_PATH_COLLECT_PAGES = 'clerk/general/collect_pages';
    const XML_PATH_COLLECT_SUBSCRIBERS = 'clerk/general/subscribers';
    /**
     * @var
     */
    private $logger;

    /**
     * @return Mage_Core_Controller_Front_Action
     * @throws Exception
     */
    public function preDispatch()
    {

        $i = Mage::getVersionInfo();
        $version = trim("{$i['major']}.{$i['minor']}.{$i['revision']}" . ($i['patch'] != '' ? ".{$i['patch']}" : "")
            . "-{$i['stability']}{$i['number']}", '.-');
        header('User-Agent: ClerkExtensionBot Magento 1/v' . $version . ' clerk/v' .(string)Mage::getConfig()->getNode()->modules->Clerk_Clerk->version . ' PHP/v' . phpversion());
        $this->logger = new ClerkLogger();

        try {
            $this->setStore();
            $this->getResponse()->setHeader('Content-type', 'application/json');

            $key = false;

            $request_body = $this->getRequest()->getRawBody();

            if($request_body){
                $request_body = json_decode($request_body) ? (array) json_decode($request_body) : array();
                $key = array_key_exists('key', $request_body) ? $request_body['key'] : false;
            }

            $publicapikey = Mage::helper('clerk')->getSetting('clerk/general/publicapikey');

            $valid_keys = (bool) $this->timingSafeEquals($publicapikey, $key);

            $header_token = $this->getHeaderToken();

            $verified_token = $this->verifyJwtToken($header_token);

            if($valid_keys && $verified_token){

                return parent::preDispatch();

            } else {

                $response = [
                    'error' => [
                        'code' => 403,
                        'message' => 'Invalid Authorization',
                        'keys_valid' => $valid_keys,
                        'token_valid' => $verified_token
                    ]
                ];

                $this->logger->warn('Invalid Authorization', ['response' => $response]);
                $this->getResponse()
                    ->setHeader('HTTP/1.1', '403', true)
                    ->setBody(json_encode($response))
                    ->sendResponse();
                exit;

            }

        } catch (Exception $e) {

            $this->logger->error('ERROR Key validation "preDispatch"', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Set current store
     */
    private function setStore()
    {
        $storeid = $this->getRequest()->getParam('store');

        if (isset($storeid) && is_numeric($storeid)) {
            try {
                Mage::app()->getStore((int)$storeid);
                Mage::app()->setCurrentStore((int)$storeid);

                return;
            } catch (Exception $e) {
                $response = [
                    'error' => [
                        'code' => 400,
                        'message' => 'Store not found',
                        'store_id' => $storeid
                    ]
                ];
            }
        } else {
            $response = [
                'error' => [
                    'code' => 400,
                    'message' => 'Query string param "store" is required'
                ]
            ];
        }

        $this->getResponse()->setBody(json_encode($response))->sendResponse();

        exit;
    }



    /**
     * Function calls JWT verification endpoint
     * @param string
     * @return bool
     */
    private function verifyJwtToken ( $token_string = null ) {

        if( ! $token_string || ! is_string( $token_string ) ) {
            return false;
        }

        $body_params = array(
            'token' => $token_string
        );

        $response = Mage::getModel('clerk/communicator')->postTokenVerification($body_params);

        if( ! $response ) {
            return false;
        }

        try {

            $rsp_array = json_decode($response, true);

            if( isset($rsp_array['status']) && $rsp_array['status'] == 'ok') {
                return true;
            }

            return false;

        } catch (\Exception $e) {

            $this->logger->error('verifyJwtToken Error', ['error' => $e->getMessage()]);

        }

    }

    private function getHeaderToken()
    {
        try {
            $token = '';
            $auth_header = $this->getRequest()->getHeader('Authorized');
            if( null !== $auth_header && is_string($auth_header)) {
                $token = count(explode(' ', $auth_header)) > 1 ? explode(' ', $auth_header)[1] : $token;
            }

            return $token;

        } catch (\Exception $e) {

            $this->logger->error('getHeaderToken Error', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Timing safe key comparison
     *
     * @return boolean
     */
    private function timingSafeEquals($safe, $user)
    {
        if(!is_string($safe) || !is_string($user)){
            return false;
        }

        $safeLen = strlen($safe);
        $userLen = strlen($user);

        if ($userLen < 8 || $safeLen < 8){
            return false;
        }

        if ($userLen != $safeLen) {
            return false;
        }

        $result = 0;

        for ($i = 0; $i < $userLen; $i++) {
            $result |= (ord($safe[$i]) ^ ord($user[$i]));
        }

        return $result === 0;
    }

    /**
     * Return Clerk module version
     */
    public function versionAction()
    {
        $this->logger = new ClerkLogger();

        try {

            $i = Mage::getVersionInfo();
            $version = trim("{$i['major']}.{$i['minor']}.{$i['revision']}" . ($i['patch'] != '' ? ".{$i['patch']}" : "")
                . "-{$i['stability']}{$i['number']}", '.-');

            $response = [
                'platform' => 'Magento',
                'platform_version' => $version,
                'clerk_version' => (string)Mage::getConfig()->getNode()->modules->Clerk_Clerk->version,
                'php_version' => phpversion()
            ];

            $this->getResponse()->setBody(json_encode($response));

        } catch (Exception $e) {

            $this->logger->error('ERROR Fetching Version "versionAction"', $e->getMessage());

        }
    }

    /**
     * Return Clerk module config
     */
    public function getconfigAction()
    {
        $this->logger = new ClerkLogger();

        try {

            $this->setStore();
            $storeid = $this->getRequest()->getParam('store');

            $response = [

                'store' => $storeid,
                'CLERK_ACTIVE' => Mage::helper('clerk')->getSetting('clerk/general/active'),
                'LANGUAGE' => Mage::helper('clerk')->getSetting('clerk/general/lang'),
                'PATH_INCLUDE_PAGES' => Mage::helper('clerk')->getSetting('clerk/general/collect_pages'),
                'PAGES_ADDITIONAL_FIELDS' => Mage::helper('clerk')->getSetting('clerk/general/pages_additional_fields'),
                'PRODUCT_SYNCHRONIZATION_REAL_TIME_ENABLED' => Mage::helper('clerk')->getSetting('clerk/general/realtime_updates'),
                'PRODUCT_SYNCHRONIZATION_COLLECT_EMAILS' => Mage::helper('clerk')->getSetting('clerk/general/collect_emails'),
                'PRODUCT_SYNCHRONIZATION_COLLECT_BASKETS' => Mage::helper('clerk')->getSetting('clerk/general/collect_baskets'),
                'PRODUCT_SYNCHRONIZATION_ADDITIONAL_FIELDS' => Mage::helper('clerk')->getSetting('clerk/general/additional_fields'),
                'PRODUCT_INCLUDE_OUT_OF_STOCK_PRODUCTS' => Mage::helper('clerk')->getSetting('clerk/general/include_out_of_stock_products'),
                'PRODUCT_SYNCHRONIZATION_VISIBILITY' => Mage::helper('clerk')->getSetting('clerk/general/only_visibility'),
                'PRODUCT_SYNCHRONIZATION_DISABLE_ORDER_SYNCHRONIZATION' => Mage::helper('clerk')->getSetting('clerk/general/disable_order_synchronization'),
                'PRODUCT_SYNCHRONIZATION_ENABLE_ORDER_RETURN_SYNCHRONIZATION' => Mage::helper('clerk')->getSetting('clerk/general/enable_order_return_synchronization'),
                'PRODUCT_SYNCHRONIZATION_IMAGE_WIDTH' => Mage::helper('clerk')->getSetting('clerk/general/image_w'),
                'PRODUCT_SYNCHRONIZATION_IMAGE_HEIGHT' => Mage::helper('clerk')->getSetting('clerk/general/image_h'),
                'PRODUCT_SYNCHRONIZATION_IMPORT_URL' => Mage::helper('clerk')->getSetting('clerk/general/url'),
                'SUBSCRIBER_SYNCHRONIZATION_ENABLED' => Mage::helper('clerk')->getSetting('clerk/general/collect_subscribers'),


                'SEARCH_ENABLED' => Mage::helper('clerk')->getSetting('clerk/search/active'),
                'SEARCH_INCLUDE_CATEGORIES' => Mage::helper('clerk')->getSetting('clerk/search/show_categories'),
                'SEARCH_CATEGORIES' => Mage::helper('clerk')->getSetting('clerk/search/categories'),
                'SEARCH_PAGES' => Mage::helper('clerk')->getSetting('clerk/search/pages'),
                'SEARCH_PAGES_TYPE' => Mage::helper('clerk')->getSetting('clerk/search/pages-type'),
                'SEARCH_TEMPLATE' => Mage::helper('clerk')->getSetting('clerk/search/template'),
                'SEARCH_NO_RESULTS_TEXT' => Mage::helper('clerk')->getSetting('clerk/search/no_results_text'),
                'SEARCH_LOAD_MORE_TEXT' => Mage::helper('clerk')->getSetting('clerk/search/load_more_text'),

                'FACETED_SEARCH_ENABLED' =>  Mage::helper('clerk')->getSetting('clerk/faceted_search/active'),
                'FACETED_SEARCH_DESIGN' => Mage::helper('clerk')->getSetting('clerk/faceted_search/design'),
                'FACETED_SEARCH_ATTRIBUTES' => Mage::helper('clerk')->getSetting('clerk/faceted_search/attributes'),
                'FACETED_SEARCH_MULTISELECT_ATTRIBUTES' => Mage::helper('clerk')->getSetting('clerk/faceted_search/multiselect_attributes'),
                'FACETED_SEARCH_TITLES' => Mage::helper('clerk')->getSetting('clerk/faceted_search/titles'),

                'LIVESEARCH_ENABLED' =>  Mage::helper('clerk')->getSetting('clerk/livesearch/active'),
                'LIVESEARCH_INCLUDE_CATEGORIES' => Mage::helper('clerk')->getSetting('clerk/livesearch/show_categories'),
                'LIVESEARCH_CATEGORIES' => Mage::helper('clerk')->getSetting('clerk/livesearch/categories'),
                'LIVESEARCH_SUGGESTIONS' => Mage::helper('clerk')->getSetting('clerk/livesearch/suggestions'),
                'LIVESEARCH_PAGES' => Mage::helper('clerk')->getSetting('clerk/livesearch/pages'),
                'LIVESEARCH_PAGES_TYPE' => Mage::helper('clerk')->getSetting('clerk/livesearch/pages-type'),
                'LIVESEARCH_DROPDOWN_POSITION' => Mage::helper('clerk')->getSetting('clerk/livesearch/dropdown-position'),
                'LIVESEARCH_TEMPLATE' => Mage::helper('clerk')->getSetting('clerk/livesearch/template'),
                'LIVESEARCH_INPUT_SELECTOR' => Mage::helper('clerk')->getSetting('clerk/livesearch/css_input_selector'),

                'POWERSTEP_ENABLED' => Mage::helper('clerk')->getSetting('clerk/powerstep/active'),
                'POWERSTEP_TYPE' => Mage::helper('clerk')->getSetting('clerk/powerstep/type'),
                'POWERSTEP_TEMPLATES' => Mage::helper('clerk')->getSetting('clerk/powerstep/templates'),
                'POWERSTEP_FILTER_DUPLICATES' => Mage::helper('clerk')->getSetting('clerk/powerstep/exclude_duplicates_powerstep'),

                'EXIT_INTENT_ENABLED' => Mage::helper('clerk')->getSetting('clerk/exit_intent/active'),
                'EXIT_INTENT_TEMPLATE' => Mage::helper('clerk')->getSetting('clerk/exit_intent/template'),

                'CATEGORY_ENABLED' => Mage::helper('clerk')->getSetting('clerk/category/enabled'),
                'CATEGORY_CONTENT' => Mage::helper('clerk')->getSetting('clerk/category/content'),
                'CATEGORY_FILTER_DUPLICATES' => Mage::helper('clerk')->getSetting('clerk/category/exclude_duplicates_category'),

                'PRODUCT_ENABLED' => Mage::helper('clerk')->getSetting('clerk/product/enabled'),
                'PRODUCT_CONTENT' => Mage::helper('clerk')->getSetting('clerk/product/content'),
                'PRODUCT_FILTER_DUPLICATES' => Mage::helper('clerk')->getSetting('clerk/product/exclude_duplicates_product'),

                'CART_ENABLED' => Mage::helper('clerk')->getSetting('clerk/cart/enabled'),
                'CART_CONTENT' => Mage::helper('clerk')->getSetting('clerk/cart/content'),
                'CART_FILTER_DUPLICATES' => Mage::helper('clerk')->getSetting('clerk/cart/exclude_duplicates_cart'),

                'LOG_ENABLED' => Mage::helper('clerk')->getSetting('clerk/log/enabled'),
                'LOG_LEVEL' => Mage::helper('clerk')->getSetting('clerk/log/level'),
                'LOG_TO' => Mage::helper('clerk')->getSetting('clerk/log/to'),

            ];

            $this->getResponse()->setBody(json_encode($response));

        } catch (Exception $e) {

            $this->logger->error('ERROR Fetching Config "getconfigAction"', $e->getMessage());

        }
    }


    public function rotatekeyAction()
    {
        try {
        $this->logger = new ClerkLogger();
        $this->setStore();
        $store_id = $this->getRequest()->getParam('store');
        $request_body = $this->getRequest()->getRawBody();

        if($request_body){
            $request_array = json_decode($request_body, true);
            if(
                isset($request_array['new_private_key']) &&
                is_string($request_array['new_private_key'])
            ){
                Mage::getConfig()->saveConfig('clerk/general/privateapikey', $request_array['new_private_key'], 'stores', $store_id);

                $this->getResponse()
                ->setHttpResponseCode(200)
                ->setHeader('Content-Type', 'application/json', true);
        
                $response = [
                    'status' => 'ok',
                    'message' => 'Changed API key for current store',
                    'store_id' => $store_id
                ];
        
                $this->getResponse()->setBody(json_encode($response));
            } else {
                $this->getResponse()
                ->setHttpResponseCode(200)
                ->setHeader('Content-Type', 'application/json', true);
        
                $response = [
                    'status' => 'error',
                    'message' => 'Could not change API keys due to invalid request body contents.',
                    'store_id' => $store_id
                ];
        
                $this->getResponse()->setBody(json_encode($response));
            }
        }


        } catch (Exception $e) {

            $this->logger->error('ERROR setting config "rotatekeyAction"', $e->getMessage());

            $this->getResponse()
            ->setHttpResponseCode(200)
            ->setHeader('Content-Type', 'application/json', true);
    
            $response = [
                'status' => 'error',
                'message' => 'Could not change API key for current store due to error.',
                'data' => $e
            ];
    
            $this->getResponse()->setBody(json_encode($response));

        }

    }

    /**
     * Set Clerk module setting
     */
    public function setconfigAction()
    {
        $this->logger = new ClerkLogger();

        try {

            $this->setStore();
            $storeid = $this->getRequest()->getParam('store');
            $post = $this->getRequest()->getRawBody();

            if($post){
                $arr_settings = json_decode($post, true);

                $count = 0;
                foreach ($arr_settings as $key => $value){

                    /**
                     * Using  - Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                     * $path - string - 'clerk/ *area* / *setting*'
                     * $value - string/int
                     * 'stores' - ($scope is set to 'stores' by default here because thats the level we use)
                     * $storeid - int - the id of the store to save the setting to
                     */

                    // generel
                    if ($key == "CLERK_ACTIVE"){
                        $path = 'clerk/general/active';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "LANGUAGE"){
                        $path = 'clerk/general/lang';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "PATH_INCLUDE_PAGES"){
                        $path = 'clerk/general/collect_pages';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "SUBSCRIBER_SYNCHRONIZATION_ENABLED"){
                        $path = 'clerk/general/collect_subscribers';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }

                    if ($key == "PAGES_ADDITIONAL_FIELDS"){
                        $path = 'clerk/general/pages_additional_fields';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "PRODUCT_SYNCHRONIZATION_REAL_TIME_ENABLED"){
                        $path = 'clerk/general/realtime_updates';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "PRODUCT_SYNCHRONIZATION_COLLECT_EMAILS"){
                        $path = 'clerk/general/collect_emails';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "PRODUCT_SYNCHRONIZATION_COLLECT_BASKETS"){
                        $path = 'clerk/general/collect_baskets';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "PRODUCT_SYNCHRONIZATION_ADDITIONAL_FIELDS"){
                        $path = 'clerk/general/additional_fields';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "PRODUCT_INCLUDE_OUT_OF_STOCK_PRODUCTS"){
                        $path = 'clerk/general/include_out_of_stock_products';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "PRODUCT_SYNCHRONIZATION_VISIBILITY"){
                        $path = 'clerk/general/only_visibility';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "PRODUCT_SYNCHRONIZATION_DISABLE_ORDER_SYNCHRONIZATION"){
                        $path = 'clerk/general/disable_order_synchronization';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "PRODUCT_SYNCHRONIZATION_ENABLE_ORDER_RETURN_SYNCHRONIZATION"){
                        $path = 'clerk/general/enable_order_return_synchronization';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "PRODUCT_SYNCHRONIZATION_IMAGE_WIDTH"){
                        $path = 'clerk/general/image_w';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "PRODUCT_SYNCHRONIZATION_IMAGE_HEIGHT"){
                        $path = 'clerk/general/image_h';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    /* - not sure about this
                    if ($key == "PRODUCT_SYNCHRONIZATION_IMPORT_URL"){
                        $path = 'clerk/general/url';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    */

                    //search
                    if ($key == "SEARCH_ENABLED"){
                        $path = 'clerk/search/active';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "SEARCH_INCLUDE_CATEGORIES"){
                        $path = 'clerk/search/show_categories';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "SEARCH_CATEGORIES"){
                        $path = 'clerk/search/categories';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "SEARCH_PAGES"){
                        $path = 'clerk/search/pages';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "SEARCH_PAGES_TYPE"){
                        $path = 'clerk/search/pages-type';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "SEARCH_TEMPLATE"){
                        $path = 'clerk/search/template';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "SEARCH_NO_RESULTS_TEXT"){
                        $path = 'clerk/search/no_results_text';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "SEARCH_LOAD_MORE_TEXT"){
                        $path = 'clerk/search/load_more_text';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }

                    //facets
                    if ($key == "FACETED_SEARCH_ENABLED"){
                        $path = 'clerk/faceted_search/active';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "FACETED_SEARCH_DESIGN"){
                        $path = 'clerk/faceted_search/design';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "FACETED_SEARCH_ATTRIBUTES"){
                        $path = 'clerk/faceted_search/attributes';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "FACETED_SEARCH_MULTISELECT_ATTRIBUTES"){
                        $path = 'clerk/faceted_search/multiselect_attributes';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "FACETED_SEARCH_TITLES"){
                        $path = 'clerk/faceted_search/titles';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }

                    // livesearch
                    if ($key == "LIVESEARCH_ENABLED"){
                        $path = 'clerk/livesearch/active';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "LIVESEARCH_INCLUDE_CATEGORIES"){
                        $path = 'clerk/livesearch/show_categories';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "LIVESEARCH_CATEGORIES"){
                        $path = 'clerk/livesearch/categories';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "LIVESEARCH_SUGGESTIONS"){
                        $path = 'clerk/livesearch/suggestions';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "LIVESEARCH_PAGES"){
                        $path = 'clerk/livesearch/pages';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "LIVESEARCH_PAGES_TYPE"){
                        $path = 'clerk/livesearch/pages-type';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "LIVESEARCH_DROPDOWN_POSITION"){
                        $path = 'clerk/livesearch/dropdown-position';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "LIVESEARCH_TEMPLATE"){
                        $path = 'clerk/livesearch/template';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "LIVESEARCH_INPUT_SELECTOR"){
                        $path = 'clerk/livesearch/css_input_selector';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }

                    // powerstep
                    if ($key == "POWERSTEP_ENABLED"){
                        $path = 'clerk/powerstep/active';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "POWERSTEP_TYPE"){
                        $path = 'clerk/powerstep/type';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "POWERSTEP_TEMPLATES"){
                        $path = 'clerk/powerstep/templates';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "POWERSTEP_FILTER_DUPLICATES"){
                        $path = 'clerk/powerstep/exclude_duplicates_powerstep';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }

                    // exit intent
                    if ($key == "EXIT_INTENT_ENABLED"){
                        $path = 'clerk/exit_intent/active';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "EXIT_INTENT_TEMPLATE"){
                        $path = 'clerk/exit_intent/template';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }

                    //category
                    if ($key == "CATEGORY_ENABLED"){
                        $path = 'clerk/category/enabled';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "CATEGORY_CONTENT"){
                        $path = 'clerk/category/content';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "CATEGORY_FILTER_DUPLICATES"){
                        $path = 'clerk/category/exclude_duplicates_category';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }

                    // product
                    if ($key == "PRODUCT_ENABLED"){
                        $path = 'clerk/product/enabled';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "PRODUCT_CONTENT"){
                        $path = 'clerk/product/content';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "PRODUCT_FILTER_DUPLICATES"){
                        $path = 'clerk/product/exclude_duplicates_product';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }

                    // cart
                    if ($key == "CART_ENABLED"){
                        $path = 'clerk/cart/enabled';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "CART_CONTENT"){
                        $path = 'clerk/cart/content';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "CART_FILTER_DUPLICATES"){
                        $path = 'clerk/cart/exclude_duplicates_product';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    // log
                    if ($key == "LOG_LEVEL"){
                        $path = 'clerk/log/level';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "LOG_TO"){
                        $path = 'clerk/log/to';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }
                    if ($key == "LOG_ENABLED"){
                        $path = 'clerk/log/enabled';
                        Mage::getConfig()->saveConfig($path, $value, 'stores', $storeid);
                        $count++;
                    }

                } // foreach

                if($count !=0){
                    Mage::getConfig()->cleanCache();
                }
            } // if post

            $this->getResponse()
                ->setHttpResponseCode(200)
                ->setHeader('Content-Type', 'application/json', true);

            $response = [

                'ok' => 'ok',
                '$arr_settings' => $arr_settings,
                'storeId' => $storeid

            ];

            $this->getResponse()->setBody(json_encode($response));

        } catch (Exception $e) {

            $this->logger->error('ERROR setting config "setconfigAction"', $e->getMessage());

        }
    }


    /**
     * Return Customers
     */
    public function customerAction()
    {
        $this->logger = new ClerkLogger();

        $storeid = $this->getRequest()->getParam('store');
        $page = $this->getIntParam('page');
        $limit = $this->getIntParam('limit');
        $customers = [];

        try {

            $collect_subscribers = Mage::getStoreConfigFlag('clerk/general/collect_subscribers');

            if($collect_subscribers){

                $_subscribers = mage::getModel('newsletter/subscriber')->getCollection()
                ->setPageSize($limit)
                ->setCurPage($page)
                ->setOrder('subscriber_id', 'desc');
                foreach ($_subscribers as $_sub) {
                    $sub = $_sub->getData();
                    $sub_object = [];
                    if($sub){
                        $unsub = $_sub->getUnsubscriptionLink();
                        $name = '';
                        $status = ($sub['subscriber_status'] == '1' || $sub['subscriber_status'] == 1) ? true : false;
                        $email = $sub['subscriber_email'];
                        $is_customer = ($sub['customer_id'] !== '0' && $sub['customer_id'] !== 0) ? true : false;
                        $id = ($is_customer) ? $sub['customer_id'] : $sub['subscriber_id'];
                        $sub_object['name'] = $name;
                        $sub_object['subscribed'] = $status;
                        $sub_object['email'] = $email;
                        $sub_object['id'] = $id;
                        $sub_object['unsub_url'] = $unsub;
                        $customers[] = $sub_object;
                    }
                }
            }

            $_customers = mage::getModel('customer/customer')->getCollection()
            ->addAttributeToSelect('firstname')
            ->addAttributeToSelect('lastname')
            ->addAttributeToSelect('postcode')
            ->addAttributeToSelect('city')
            ->addAttributeToSelect('email')
            ->addAttributeToFilter('store_id', $storeid)
            ->setPageSize($limit)
            ->setCurPage($page);

            $collect_emails = Mage::getStoreConfigFlag('clerk/general/collect_emails');

            if($collect_subscribers){
                foreach ($_customers as $_customer) {
                    $customer = $_customer->getData();
                    $email = $customer['email'];
                    $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($email);
                    $unsub = Mage::getModel('newsletter/subscriber')->loadByEmail($email)->getUnsubscriptionLink();
                    $status = false;
                    if($subscriber){
                        $status = $subscriber->getData('subscriber_status') == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED;
                    }
                    $customers[] = [
                        'id' => $customer['entity_id'],
                        'name' => $customer['firstname'] . ' ' . $customer['lastname'],
                        'email' => $customer['email'],
                        'subscribed' => $status,
                        'unsub_url' => $unsub,
                    ];
                }
            } else {
                foreach ($_customers as $_customer) {
                    $customer = $_customer->getData();

                    if($collect_emails){
                        $customer_email = $customer['email'];
                    } else {
                        $customer_email = '';
                    }

                    $customers[] = [
                        'id' => $customer['entity_id'],
                        'name' => $customer['firstname'] . ' ' . $customer['lastname'],
                        'email' => $customer_email,
                    ];
                }
            }

            $this->getResponse()->setBody(json_encode($customers));

        } catch (Exception $e) {

            $this->logger->error('ERROR Fetching Version "customerAction"', $e->getMessage());

        }
    }

    public function pluginAction()
    {
        $this->logger = new ClerkLogger();

        try {

            $modules = Mage::getConfig()->getNode('modules')->children();
            $respponse = (array)$modules;

            $this->getResponse()->setBody(json_encode($respponse));

        } catch (Exception $e) {

            $this->logger->error('ERROR Fetching Plugin\'s "pluginAction"', $e->getMessage());

        }
    }

    /**
     * This endpoint will list stores
     *
     */
    public function storeAction()
    {
        $this->logger = new ClerkLogger();
        try {

            $data = [];

            foreach (Mage::helper('clerk')->getAllStores() as $store) {
                $data[] = [
                    'id' => $store->getId(),
                    'name' => $store->getName(),
                    'active' => (bool)Mage::getStoreConfig('clerk/general/active', $store),
                ];
            }

            $this->getResponse()->setBody(json_encode($data));

        } catch (Exception $e) {

            $this->logger->error('ERROR Fetching Store "storeAction"', $e->getMessage());

        }
    }

    /**
     * Product endpoint for collection and single products
     *
     */
    public function productAction()
    {
        $this->logger = new ClerkLogger();
        try {

            // Handler for product endpoint. E.g.
            // https://store.com/clerk/api/product/id/24
            $id = $this->getRequest()->getParam('id', false);

            if ($id) {
                $id = $this->getIntParam('id');
                if (Mage::helper('clerk')->isProductIdValid($id)) {
                    $response = Mage::getModel('clerk/product')->load($id)->getInfo();
                } else {
                    $response = [
                        'error' => [
                            'code' => 404,
                            'message' => 'Product not found',
                            'product_id' => $id
                        ]
                    ];
                }
            } else {

                $page = $this->getIntParam('page');
                $limit = $this->getIntParam('limit');
                $page = Mage::getModel('clerk/productpage')->load((int)$page, $limit);

                $response = $page->array;
                $this->getResponse()->setHeader('Total-Page-Count', $page->totalPages);
            }

            $this->logger->log('Products Fetched', ['response' => $response]);
            $this->getResponse()->setBody(json_encode($response));

        } catch (Exception $e) {

            $this->logger->error('ERROR Products Synchronization "productAction"', $e->getMessage());

        }
    }

    /**
     * @param $key
     * @param null $errmsg
     * @return int
     * @throws Exception
     */
    private function getIntParam($key, $errmsg = null)
    {
        $this->logger = new ClerkLogger();
        try {

            $value = $this->getRequest()->getParam($key);

            if (!is_numeric($value)) {
                $this->getResponse()->setHeader('HTTP/1.0', '400', true);

                if (isset($errmsg)) {
                    $response = [
                        'error' => [
                            'code' => 400,
                            'message' => $errmsg,
                            'value' => $value
                        ]
                    ];
                } else {
                    $response = [
                        'error' => [
                            'code' => 400,
                            'message' => "Query string '" . $key . "' is required and must be integer",
                            'value' => $value
                        ]
                    ];
                }

                $this->getResponse()->setBody(json_encode($response))->sendResponse();
                exit;
            }

            return (int)$value;

        } catch (Exception $e) {

            $this->logger->error('ERROR Fetching Parameters "getIntParam"', $e->getMessage());

        }
    }

    public function pageAction()
    {

        $this->logger = new ClerkLogger();
        try {
            if (Mage::getStoreConfigFlag(self::XML_PATH_COLLECT_PAGES)) {

                if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')

                    $Url = "https://" . $_SERVER['HTTP_HOST'];

                else {

                    $Url = "https://" . $_SERVER['HTTP_HOST'];

                }

                $items = array();
                $Additional_Fields = explode(',', Mage::getStoreConfig('clerk/general/pages_additional_fields'));
                $pages = Mage::getModel('cms/page')->getCollection();

                foreach ($pages as $page) {

                    $item = [];
                    $url = Mage::helper('cms/page')->getPageUrl($page->page_id);
                    $item['id'] = $page->page_id;
                    $item['type'] = 'cms page';
                    $item['url'] = $url;
                    $item['title'] = $page->title;
                    $item['text'] = $page->content;

                    if (!$this->ValidatePage($item)) {

                        continue;

                    }

                    if (!empty($Additional_Fields)) {

                        foreach ($Additional_Fields as $Additional_Field) {

                            try {

                                if ($page->{str_replace(' ', '', $Additional_Field)} != null) {

                                    $item[str_replace(' ', '', $Additional_Field)] = $page->{str_replace(' ', '', $Additional_Field)};

                                }else {

                                    continue;

                                }

                            } catch (Exception $e) {

                                continue;

                            }

                        }

                    }

                    $items[] = $item;
                }

                $this->logger->log('Pages Fetched', ['response' => json_encode($items)]);
                $this->getResponse()->setBody(json_encode($items));
            } else {

                $this->getResponse()->setBody(json_encode([]));

            }

        } catch (Exception $e) {

            $this->logger->error('ERROR Page Synchronization "pageAction"', $e->getMessage());

        }

    }

    /**
     * @param $Page
     * @return bool
     */
    public function ValidatePage($Page) {

        foreach ($Page as $key => $content) {

            if (empty($content)) {

                return false;

            }

        }

        return true;

    }

    /**
     * @throws Exception
     */
    public function categoryAction()
    {
        $this->logger = new ClerkLogger();
        try {

            $page = $this->getIntParam('page');
            $limit = $this->getIntParam('limit');

            $rootCategoryId = Mage::app()->getStore()->getRootCategoryId();

            $categories = Mage::getModel('catalog/category')
                ->getCollection()
                ->addIsActiveFilter()
                ->addAttributeToSelect('name')
                ->addAttributeToFilter('path', ['like' => "1/{$rootCategoryId}/%"])
                ->setOrder('entity_id', Varien_Db_Select::SQL_ASC)
                ->setPageSize($limit)
                ->setCurPage($page);

            $items = [];

            foreach ($categories as $category) {
                //Get children categories
                $children = $category->getResource()->getChildren($category, false);

                $data = [
                    'id' => (int)$category->getId(),
                    'name' => $category->getName(),
                    'url' => $category->getUrl(),
                    'subcategories' => array_map('intval', $children),
                ];

                $items[] = $data;
            }

            $this->getResponse()->setHeader('Total-Page-Count', $categories->getLastPageNumber());

            if ($page > $categories->getLastPageNumber()) {
                $this->getResponse()->setBody(json_encode([]));
            } else {
                $this->logger->log('Categories Fetched', ['response' => $items]);
                $this->getResponse()->setBody(json_encode($items));
            }

        } catch (Exception $e) {

            $this->logger->error('ERROR Category Synchronization "categoryAction"', $e->getMessage());

        }
    }

    /**
     * Endpoint for order import
     *
     */
    public function orderAction()
    {
        $this->logger = new ClerkLogger();
        try {

            $page = $this->getIntParam('page');
            $limit = $this->getIntParam('limit');
            $start_date = $this->getRequest()->getParam('start_date');
            $end_date = $this->getRequest()->getParam('end_date');
            $days = $this->getIntParam('days');

            if (Mage::getStoreConfigFlag('clerk/general/disable_order_synchronization')) {
                $this->logger->log('Order Synchronization is disabled', ['response' => '']);
                $this->getResponse()->setBody(json_encode([]));
            } else {
                $page = Mage::getModel('clerk/orderpage')->load($page, $limit, $start_date, $end_date, $days);
                $this->logger->log('Order Fetched', ['response' => '']);
                $this->getResponse()->setHeader('Total-Page-Count', $page->totalPages);
                $this->getResponse()->setBody(json_encode($page->array));
            }

        } catch (Exception $e) {

            $this->logger->error('ERROR Order Synchronization "orderAction"', $e->getMessage());

        }
    }

    /**
     * This endpoint will list current cart products
     *
     */
    public function cartAction()
    {
        try {

            $cart_products = Mage::getModel('checkout/cart')->getQuote()->getAllVisibleItems();
            $cart_product_ids = array();

            foreach ($cart_products as $product) {
                if (!in_array($product->getProduct()->getId(), $cart_product_ids)) {
                    $cart_product_ids[] = $product->getProduct()->getId();
                }
            }

            $this->getResponse()->setBody(json_encode($cart_product_ids));

        } catch (Exception $e) {

            $this->logger->error('ERROR Fetching Store "cartAction"', $e->getMessage());

        }
    }

}
