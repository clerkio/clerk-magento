<?php

class Clerk_Clerk_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Returns product attribute if present
     *
     * @param $product
     * @param $attribute
     * @return null|string
     */
    public function getAttributeSafe($product, $attribute)
    {
        $eavConfig = Mage::getModel('eav/config');
        /* @var $eavConfig Mage_Eav_Model_Config */

        $attributes = $eavConfig->getEntityAttributeCodes(
            Mage_Catalog_Model_Product::ENTITY, $product
        );

        if (in_array($attribute, $attributes)) {
            $value = trim($product->getAttributeText($attribute));

            return $value ? $value : null;
        }
    }

    /**
     * Returns product with min price from grouped product
     *
     * @param $product
     * @return null
     */
    public function getMinPricedProductFromGroup($product)
    {
        $choosen = null;
        $associated = $product->getTypeInstance(true)
            ->getAssociatedProducts($product);
        foreach ($associated as $_product) {
            if ($choosen == null || $choosen->getFinalPrice() >
                                    $_product->getFinalPrice()) {
                $choosen = $_product;
            }
        }

        return $choosen;
    }

    /**
     * Determine if floats are equal
     *
     * @param $f1
     * @param $f2
     * @return bool
     */
    public function floatEq($f1, $f2)
    {
        return abs($f1 - $f2) < 0.01;
    }

    public function getEndpointForContent($storeId, $contentId)
    {
        $contentResponse = Mage::getModel('clerk/communicator')->getContent($storeId);
        $contentResult = json_decode($contentResponse->getBody());

        if ($contentResult && $contentResult->status === 'ok') {
            foreach ($contentResult->contents as $content) {
                if ($content->type !== 'html') {
                    continue;
                }

                if ($content->id === $contentId) {
                    return $content->api;
                }
            }
        }

        return '';
    }

    /**
     * Deteremine if a product id is valid
     *
     * @param $productId
     * @return bool
     */
    public function isProductIdValid($productId)
    {
        $product = Mage::getModel('catalog/product')->load($productId);

        return (bool) $product->getId();
    }

    /**
     * Get an array of store objects
     *
     * @return array
     */
    public function getAllStores()
    {
        $data = [];

        foreach (Mage::app()->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    $data[] = $store;
                }
            }
        }

        return $data;
    }

    /**
     * Returns the config value if extension is active
     *
     * @param $path
     * @param null $store
     * @return string|void
     */
    public function getSetting($path, $store = null)
    {
        if (!Mage::getStoreConfig('clerk/general/active', $store)) {
            return;
        }

        return trim((string) Mage::getStoreConfig($path, $store));
    }

    /**
     * Validates the JWT
     *
     */
    public function validateJwt($header)
	{

		$parts = explode(' ', $header);

		if (count($parts) !== 2) {
			return false;
		}
		if ($parts[0] !== 'Bearer') {
			return false;
		}

		$jwt = $parts[1];
        $publicKey = Mage::helper('clerk')->getSetting('clerk/general/publicapikey');

		$query_params_array = [
			'token' => $jwt,
			'key' => $publicKey
		];
		try {
			$response = Mage::getSingleton('clerk/communicator')->getTokenVerify($query_params_array);

			$responseBody = $response->getBody();

			$responseBody = json_decode($responseBody, true);

			if (isset($responseBody["status"]) && $responseBody["status"] === 'ok') {
				return true;
			}

		} catch (Exception $e) {
			return false;
		}
	}

    /**
     * Get parameters for endpoint
     *
     * @param $endpoint
     * @return array
     */
    public function getParametersForEndpoint($endpoint)
    {
        $endpointMap = [
            'search/search' => [
                'query',
                'limit'
            ],
            'search/predictive' => [
                'query',
                'limit'
            ],
            'search/categories' => [
                'query',
                'limit'
            ],
            'search/suggestions' => [
                'query',
                'limit'
            ],
            'search/popular' => [
                'query',
                'limit'
            ],
            'recommendations/popular' => [
                'limit'
            ],
            'recommendations/trending' => [
                'limit'
            ],
            'recommendations/currently_watched' => [
                'limit'
            ],
            'recommendations/popular' => [
                'limit'
            ],
            'recommendations/keywords' => [
                'limit',
                'keywords'
            ],
            'recommendations/complementary' => [
                'limit',
                'products'
            ],
            'recommendations/substituting' => [
                'limit',
                'products'
            ],
            'recommendations/category/popular' => [
                'limit',
                'category'
            ],
            'recommendations/category/trending' => [
                'limit',
                'category'
            ],
            'recommendations/visitor/history' => [
                'limit',
            ],
            'recommendations/visitor/complementary' => [
                'limit',
            ],
            'recommendations/visitor/substituting' => [
                'limit',
            ],
            'recommendations/customer/history' => [
                'limit',
                'email'
            ],
            'recommendations/customer/complementary' => [
                'limit',
                'email'
            ],
            'recommendations/customer/substituting' => [
                'limit',
                'email'
            ],
        ];

        if (array_key_exists($endpoint, $endpointMap)) {
            return $endpointMap[$endpoint];
        }

        return [];
    }
}
