Clerk for Magento
=================

[Clerk](http://www.clerk.io) io is a international tech-startup based in
Copenhagen targeting the eCommerce industry. We help webshops sell more
through an intelligent search function and product recommendations.

This extension replaces the default search of Magento with a typo-tolerant,
fast & relevant search experience backed by Clerk.io and enables product
recommendations.

See features and benefits of [Clerk for
Magento](https://help.clerk.io/getting-started/magento).

![Latest version](https://img.shields.io/badge/latest-1.2.3-green.svg)
![Magento 1.7.1](https://img.shields.io/badge/magento-1.7.1-blue.svg)
![Magento 1.8.1](https://img.shields.io/badge/magento-1.8.1-blue.svg)
![Magento 1.9.2](https://img.shields.io/badge/magento-1.9-blue.svg)
![PHP >= 5.3](https://img.shields.io/badge/php-%3E=5.3-green.svg)

Demo
--------------

TODO


Installation
--------------

Rewrite for clerk install

To setup this module, you'll need an Clerk account.

  1. Create an [Clerk Account](https://www.clerk.io).
  2. Download the packaged Community Extension from [the magento-connect store](http://www.magentocommerce.com/magento-connect/clerk-search-extension.html)
  3. Install it on your Magento instance.
  4. Configure your credentials from the **System** > **Configuration** > **Catalog** > **Clerk** administration panel.
  5. Force the re-indexing of all your products, categories with the **System > Index Management > Clerk Search** index.
  6. Force the re-indexing of all your pages with the **System > Index Management > ClerkSearch Pages** index.
  7. Force the re-indexing of all your suggestions with the **System > Index Management > Clerk Search Suggestions** index.

**Note:** If you experience a 404 issue while accessing
the *Clerk* administration panel, can follow this
[procedure](http://www.fanplayr.com/1415/magento-404-error-page-not-found-in-c
onfiguration/).

Features
--------

#### Typo-tolerant full-text search

If you choose not to use the instant search. The extension will replace the
fulltext indexer providing you a typo-tolerant & relevant search experience.

If you choose to use the instant search, when you search for something
fulltext indexer replacement is still used so that you can have a backend
implementation of the search in order to keep a good SEO

#### Product recommendation sliders

This extension adds a default implementation of an instant & faceted search
results page. Just customize the underlying CSS & JavaScript to suits your shop
theme.

Contribute to the Extension
------------
TODO


