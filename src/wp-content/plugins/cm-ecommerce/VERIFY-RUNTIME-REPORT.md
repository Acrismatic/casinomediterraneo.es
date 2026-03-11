# Verify Runtime Report

Change: `reorganizacion-screaming-architecture-assets`
Date: `2026-03-11`
Environment: `docker local (compose + compose.dev)`

## Gate 1 - Environment

- `php -v` host: `8.1.2`
- WordPress/WooCommerce runtime: running in Docker (`wordpress`, `db`, `nginx`)
- WP-CLI: available in container via `/tmp/wp-cli.phar`
- Plugin activation:
  - `woocommerce/woocommerce.php`: active
  - `cm-ecommerce/cm-ecommerce.php`: active
- Seeded data created:
  - `torneo` product: `37225`
  - `evento` product: `37226`
  - `simple` product: `37228`
  - shortcode page: `37227` (`/sdd-productos/`)
  - `casino_poker_torneo` ref: `37230`

Result: `PASS`

## Gate 2 - Smoke Tests

### Admin checks

Executed with `WP_ADMIN=true` runtime script `tools/runtime_gate_admin.php`.

- Product types `torneo-poker` and `evento`: PASS
- Tab `torneo_info_product_data`: PASS
- Script handle `cm-wc-product-type` registered: PASS
- Dependencies present: `jquery`, `wc-enhanced-select`, `wc-admin-product-meta-boxes`: PASS

### Frontend checks

Executed via HTTP against local runtime.

- `[PRODUCTOS]` default class `cm-productos--default`: PASS
- `[productos_torneo]` class `cm-productos--torneo`: PASS
- `[PRODUCTOS tipo="evento"]` class `cm-productos--evento`: FAIL
- Style handle `cm-shortcode-productos-css`: PASS
- Script handle `wc-add-to-cart-js`: PASS
- Single product style handle `cm-single-product-css`: PASS

### Add-to-cart check

- Request `/?add-to-cart=37225`: HTTP `200`
- Cart reflects item presence: PASS

Result: `PARTIAL`

## Gate 3 - Rollback Drill

Simulated template rollback executed:

1. Temporarily moved module template:
   - `includes/Modules/Catalog/SingleProduct/Templates/woocommerce/single-product.php`
2. Requested `http://localhost:8080/product/sdd-torneo-poker/`
3. Response remained healthy (`HTTP 200`, body bytes `54145`)
4. Restored template file to original location

Result: `PASS` (simulated rollback)

## Final Verdict

- Gate 1: `PASS`
- Gate 2: `PARTIAL`
- Gate 3: `PASS`
- Overall: `NO-CLOSE`

Reason:
- Core runtime and rollback checks are healthy.
- Frontend variant scenario legacy `[PRODUCTOS tipo="torneo"]` ya no aplica por corte directo; verificar contrato nuevo con `[productos_torneo]` y endpoint AJAX.

## Next Actions

1. Investigate why shortcode variant queries return empty output despite seeded product types (`torneo-poker`, `evento`).
2. Apply corrective patch in Catalog/ProductTypes query path.
3. Re-run this report and then close `sdd-verify`.
