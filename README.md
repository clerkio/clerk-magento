# Clerk.io for Magento 1

The official [Clerk.io](https://clerk.io) extension for Magento 1. It connects your store to Clerk.io's AI platform, giving you personalized search, product recommendations, and visitor tracking out of the box.

**Version:** 4.8.6 · **PHP:** 5.3+ · **Magento:** 1.9.x

> **Note:** Magento 1 has reached end-of-life. This extension is maintained for existing installations. New stores should use [Magento 2](https://github.com/clerkio/clerk-magento2) or another supported platform.

---

## What It Does

This extension handles three things:

1. **Feeds your product data to Clerk.io** — Exposes controller endpoints (`/clerk/api/`) that the Clerk.io platform calls to sync your catalog. Also pushes real-time updates when products are saved, deleted, or when catalog rules/stock changes.

2. **Renders Clerk.io on the frontend** — Injects [Clerk.js](https://docs.clerk.io/docs/clerkjs-quick-start) on every page, which handles search results, live search dropdowns, recommendation sliders, exit-intent popups, and tracking.

3. **Tracks visitor behavior** — Logs page views, clicks, cart contents, completed orders, and refunds so Clerk.io's AI can learn and improve results.

### Feature Overview

| Feature | What it does |
|---------|-------------|
| **Search** | Replaces Magento's native search results page with Clerk.io's. Overrides `CatalogSearch/ResultController`. |
| **Live Search** | Type-ahead dropdown. Configurable input selector, suggestions/categories/pages count, dropdown position. |
| **Faceted Search** | Configurable facet attributes with labels, multiselect support, and custom design template. |
| **Recommendations** | Sliders on product, category, cart pages. Also available as a Magento widget for CMS pages. |
| **Powerstep** | After add-to-cart, shows a page or popup with recommendations. Observes `checkout_cart_add_product_complete`. |
| **Exit Intent** | Overlay triggered when the visitor moves to leave the page. |
| **Sales Tracking** | `<span>` on checkout success page that logs the order to Clerk.io. |
| **Basket Tracking** | Observes `checkout_cart_save_after` to sync cart contents to Clerk.io. |
| **Real-Time Sync** | Observes `catalog_product_save_commit_after`, `catalog_product_delete_before`, `catalog_product_attribute_update_after`, `catalogrule_rule_save_after`, and `clean_catalog_images_cache_after`. |
| **Return Tracking** | Observes `sales_order_creditmemo_save_after` to track refunded orders. |

---

## Installation

1. Download the latest release from [GitHub](https://github.com/clerkio/clerk-magento/releases).
2. Install via Magento Connect or extract into your Magento root directory preserving the folder structure.
3. **Log out and back in** to the admin panel (required for ACL refresh).
4. Configure at **System > Configuration > Clerk > Settings**.

Enter your API keys from [my.clerk.io](https://my.clerk.io).

Full setup guide: [help.clerk.io/integrations/magento-1/extension](https://help.clerk.io/integrations/magento-1/extension/)

---

## How It Works Under the Hood

### Data Feed Endpoints

All under the `/clerk/api/` route. Auth logic is in `ApiController.php`.

| Endpoint | What it returns |
|----------|----------------|
| `/clerk/api/product` | Products with prices, stock, images, categories, child product data, additional fields |
| `/clerk/api/order` | Orders with line items, quantities, prices, email, customer ID |
| `/clerk/api/category` | Category tree with parent/child relationships |
| `/clerk/api/customer` | Customers and subscribers |
| `/clerk/api/page` | CMS pages |
| `/clerk/api/version` | Magento version, extension version, PHP version |

There are also diagnostic and configuration management endpoints available for Clerk.io platform integration.

### Magento Events Observed

**Global scope:**

| Event | What it does |
|-------|-------------|
| `catalog_product_save_commit_after` | Real-time product sync to Clerk.io API |
| `catalog_product_attribute_update_after` | Sync on mass attribute update |
| `checkout_cart_save_after` | Basket tracking to Clerk.io API |
| `checkout_cart_add_product_complete` | Powerstep popup trigger |

**Frontend scope:**

| Event | What it does |
|-------|-------------|
| `controller_action_layout_generate_blocks_after` | Layout manipulation for search |
| `core_block_abstract_to_html_before` | Block rendering modifications |

**Admin scope:**

| Event | What it does |
|-------|-------------|
| `catalog_product_delete_before` | Real-time product removal from Clerk.io |
| `catalogrule_rule_save_after` | Re-sync products when catalog price rules change |
| `clean_catalog_images_cache_after` | Re-sync products when image cache is flushed |
| `sales_order_creditmemo_save_after` | Track refunded orders |

### Frontend Router Overrides

The extension overrides two Magento frontend routers:

- **`catalogsearch`** — `Clerk_Clerk_CatalogSearch` is loaded **before** `Mage_CatalogSearch`, replacing the search results controller.
- **`checkout`** — `Clerk_Clerk_Checkout` is loaded **after** `Mage_Checkout`, extending the cart controller for powerstep.

---

## Structure

```
├── code/
│   ├── controllers/
│   │   ├── ApiController.php              ← Data feed API (product, order, category, etc.)
│   │   ├── CatalogSearch/ResultController.php ← Overrides native search results
│   │   ├── Checkout/CartController.php    ← Extends cart for powerstep redirect
│   │   ├── BasketController.php           ← Cart contents endpoint
│   │   ├── ClerkLogger.php                ← Logging
│   │   └── Adminhtml/ClerkController.php  ← Admin dashboard controllers
│   │
│   ├── Model/
│   │   ├── Observer.php                   ← All event observers (product sync, basket, powerstep, refunds)
│   │   ├── Config.php                     ← Config path constants
│   │   ├── Communicator.php               ← Clerk.io API client
│   │   ├── Catalog/Product.php            ← Product model rewrite
│   │   ├── Catalog/Productbase.php        ← Product data builder
│   │   ├── Productpage.php                ← Product page data
│   │   └── Orderpage.php                  ← Order page data
│   │
│   ├── Block/
│   │   ├── Tracking.php                   ← Clerk.js init block
│   │   ├── Search.php                     ← Search results block
│   │   ├── SalesTracking.php              ← Order tracking block
│   │   ├── Powerstep.php                  ← Powerstep block
│   │   ├── ExitIntent.php                 ← Exit intent block
│   │   ├── Widget/Content.php             ← Recommendations widget
│   │   └── Adminhtml/Dashboard.php        ← Admin dashboard (my.clerk.io iframe)
│   │
│   ├── Helper/Data.php                    ← Data helper
│   │
│   └── etc/
│       ├── config.xml                     ← Module config (routes, observers, events, defaults)
│       ├── system.xml                     ← Admin configuration fields
│       ├── widget.xml                     ← Clerk Content widget definition
│       └── adminhtml.xml                  ← ACL & admin menu
│
├── design/
│   └── frontend/base/default/
│       ├── layout/clerk.xml               ← Frontend layout (block placement)
│       └── template/clerk/
│           ├── tracking.phtml             ← Clerk.js loader + config + basket tracking
│           ├── search.phtml               ← Search results page
│           ├── livesearch.phtml           ← Live search span
│           ├── facets.phtml               ← Faceted search container
│           ├── salestracking.phtml        ← Order tracking span
│           ├── powerpage.phtml            ← Powerstep page template
│           ├── powerpopup.phtml           ← Powerstep popup template
│           ├── exitintent.phtml           ← Exit intent span
│           └── widget/content.phtml       ← Recommendation widget
│
├── etc/modules/Clerk_Clerk.xml            ← Module declaration
│
└── devenv/                                ← Docker development environment
    └── restart.sh
```

---

## Customizing & Extending

If you need to customize the extension, here are the parts to be careful with.

**`CatalogSearch/ResultController.php` overrides native search.** The extension registers its router **before** `Mage_CatalogSearch`, completely replacing the search results page. If another extension also overrides `CatalogSearch`, there will be a conflict.

**`Checkout/CartController.php` extends the cart controller.** Registered **after** `Mage_Checkout` to add the powerstep redirect after add-to-cart. Other extensions modifying the cart controller need to be aware of this.

**`Model/Catalog/Product.php` is a model rewrite.** The extension rewrites the product model via `config.xml` (`<rewrite><product>Clerk_Clerk_Model_Catalog_Product</product></rewrite>`). Only one module can rewrite a model — this will conflict with other extensions that rewrite the same class.

**All observers are in one file.** `Model/Observer.php` contains every event observer. If you need to modify observer behavior, this is the single file to look at.

**Local code pool overrides.** Copy any class from `app/code/community/Clerk/Clerk/` to `app/code/local/Clerk/Clerk/` to override it safely. Magento loads `local` before `community`.

**Template overrides.** Copy templates from `app/design/frontend/base/default/template/clerk/` to your theme's template directory.

**Layout overrides.** Override `clerk.xml` in your theme's layout directory to move, remove, or add blocks.

---

## Development Environment

A Docker environment is included for local development.

```bash
# Start Magento 1.9.2 with sample data + Clerk extension
./devenv/restart.sh -b http://$(docker ip)/
```

| Service | URL | Credentials |
|---------|-----|-------------|
| Admin | `http://[docker-ip]/admin` | `admin` / `magentorocks1` |
| phpMyAdmin | `http://[docker-ip]/phpmyadmin` | — |

Shell access:

```bash
docker exec -i -t clerk-magento /bin/bash
```

---

## Troubleshooting

- **Extension not appearing:** Check `app/etc/modules/Clerk_Clerk.xml` exists. Clear cache (`rm -rf var/cache/*`). Log out and back in to admin.
- **Config not showing:** Log out and back in (ACL refresh required after install).
- **Search not working:** Verify API keys in **System > Configuration > Clerk**.
- **Products missing from feed:** Visit `/clerk/api/product?limit=1` (with auth) to check the feed. Check product visibility settings.
- **Real-time sync not working:** Check `realtime_updates` is enabled in config. Check `var/log/clerk_log.log` for errors.

Enable logging at **System > Configuration > Clerk > Logging Settings** — set to "File" to write to `var/log/clerk_log.log`.

---

## Links

- [Setup Guide](https://help.clerk.io/integrations/magento-1/extension/)
- [Data Sync Docs](https://help.clerk.io/integrations/magento-1/sync-data/)
- [Clerk.js Docs](https://docs.clerk.io/docs/clerkjs-quick-start)
- [Design Template Language](https://docs.clerk.io/docs/clerkjs-template-language)
- [API Reference](https://docs.clerk.io/reference)
- [Dashboard](https://my.clerk.io)

---

## Contributing

1. Fork and branch from `master`
2. Use the included Docker environment for testing
3. Open a PR with a clear description

Issues & feature requests: [github.com/clerkio/clerk-magento/issues](https://github.com/clerkio/clerk-magento/issues)

---

## Support

- [help.clerk.io](https://help.clerk.io) · [support@clerk.io](mailto:support@clerk.io) · [status.clerk.io](https://status.clerk.io)
