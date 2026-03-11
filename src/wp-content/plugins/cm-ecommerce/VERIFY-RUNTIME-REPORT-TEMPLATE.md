# Verify Runtime Report Template

Change: `reorganizacion-screaming-architecture-assets`
Date: `YYYY-MM-DD`
Environment: `staging|production-like`
Verifier: `name`

## 1) Prerequisites

- [ ] `php -v` available
- [ ] `wp --info` available
- [ ] WordPress active
- [ ] WooCommerce active
- [ ] `cm-ecommerce` active
- [ ] Seed data ready (`torneo-poker`, `evento`, page with `[productos_torneo]`)

Command evidence:

```text
php -v
wp --info
wp core version
wp plugin is-active woocommerce && echo "WC OK"
wp plugin is-active cm-ecommerce && echo "CM OK"
wp eval 'echo did_action("woocommerce_loaded") ? "woocommerce_loaded=1\n" : "woocommerce_loaded=0\n";'
wp eval 'echo class_exists("CM_Plugin_Bootstrap") ? "bootstrap_class=1\n" : "bootstrap_class=0\n";'
```

Result: `PASS|FAIL`

## 2) Admin Smoke (ProductTypes)

- [ ] Product type selector shows `torneo-poker` and `evento`
- [ ] `cm-wc-product-type` enqueued in `post.php/post-new.php`
- [ ] Dependencies include `jquery,wc-enhanced-select,wc-admin-product-meta-boxes`
- [ ] `torneo_info_product_data` panel visible for `torneo-poker`
- [ ] AJAX `cm_search_torneos` responds correctly
- [ ] `_cm_torneo_id` saves and persists

Evidence links/notes:
- Screenshot(s):
- Network capture:
- Log lines:

Result: `PASS|FAIL`

## 3) Frontend Smoke (Catalog)

- [ ] `[PRODUCTOS]` renders
- [ ] `[productos_torneo]` renders
- [ ] `[productos_torneo cantidad="8"]` renders 8 items
- [ ] `[productos_torneo cantidad="abc"]` fallback to 3 items
- [ ] `[PRODUCTOS tipo="evento"]` renders
- [ ] Handle `cm-shortcode-productos` present
- [ ] Handles `cm-shortcode-productos-torneo-css/js` present
- [ ] Dependency `wc-add-to-cart` present
- [ ] AJAX `cm_productos_torneo_load_more` success contract (`loaded_count=10`)
- [ ] AJAX nonce invalido retorna `invalid_nonce`
- [ ] AJAX agotamiento retorna `has_more=false`
- [ ] Single product (`torneo-poker`) renders with `cm-single-product`
- [ ] Single product (`evento`) renders with `cm-single-product`
- [ ] Add-to-cart works from shortcode and single

Evidence links/notes:
- URL(s):
- Screenshot(s):
- Console/log lines:

Result: `PASS|FAIL`

## 4) Template Locate and Shim

- [ ] `woocommerce_locate_template` resolves module template first
- [ ] Legacy shim works as fallback
- [ ] Theme override (if present) remains respected

Expected template paths:
- `includes/Modules/Catalog/SingleProduct/Templates/woocommerce/single-product.php`
- `templates/woocommerce/single-product.php` (shim/fallback)

Evidence links/notes:
- Debug output:
- Screenshots:

Result: `PASS|FAIL`

## 5) Rollback Drill

Selected mode: `REAL|SIMULATED`

### Real rollback (recommended)
- [ ] Reverted complete plugin package to pre-change release
- [ ] Re-ran admin smoke
- [ ] Re-ran frontend smoke
- [ ] No fatal errors in logs

### Simulated rollback (fallback)
- [ ] Forced legacy template/assets path in controlled way
- [ ] Re-ran equivalent smoke tests

Recovery timing:
- Start:
- End:
- Duration:

Evidence links/notes:
- Steps executed:
- Logs:

Result: `PASS|FAIL`

## 6) Final Matrix

| Module | Check | Result | Evidence |
|---|---|---|---|
| ProductTypes | Handle `cm-wc-product-type` | PASS/FAIL | |
| ProductTypes | WC deps intact | PASS/FAIL | |
| ProductTypes | Types `torneo-poker` and `evento` | PASS/FAIL | |
| ProductTypes | Torneo panel + AJAX | PASS/FAIL | |
| Catalog | `[PRODUCTOS]` default | PASS/FAIL | |
| Catalog | `[productos_torneo]` default=3 | PASS/FAIL | |
| Catalog | `[productos_torneo cantidad="8"]` | PASS/FAIL | |
| Catalog | `[productos_torneo cantidad="abc"]` fallback | PASS/FAIL | |
| Catalog | `[PRODUCTOS tipo="evento"]` | PASS/FAIL | |
| Catalog | Handles `cm-shortcode-productos` + `cm-shortcode-productos-torneo-css/js` + deps | PASS/FAIL | |
| Catalog | AJAX load more + nonce + end-of-list | PASS/FAIL | |
| Catalog | Single template resolved correctly | PASS/FAIL | |
| Catalog | Handle `cm-single-product` | PASS/FAIL | |
| Catalog | Add-to-cart end-to-end | PASS/FAIL | |
| Bootstrap/Shared | Modules boot without fatal | PASS/FAIL | |
| Rollback | Drill completed and validated | PASS/FAIL | |

## 7) Verdict

- Status: `PASS|PASS_WITH_WARNINGS|FAIL`
- Executive summary:
  -
  -
  -

## 8) Next Actions

- If `PASS`: proceed to `/sdd-archive reorganizacion-screaming-architecture-assets`
- If `PASS_WITH_WARNINGS`: schedule real rollback drill and close warnings
- If `FAIL`: run `/sdd-apply` corrective batch for failed checks
