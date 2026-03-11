# AGENTS.md

Guía para agentes de desarrollo en este repositorio WordPress/WooCommerce.

Objetivo: producir cambios seguros, mantenibles y coherentes con buenas prácticas de PHP 8, WordPress y WooCommerce.

## 1) Principios Base

- Priorizar compatibilidad, seguridad y legibilidad por encima de "magia" o soluciones rápidas.
- Mantener cambios pequeños y enfocados. Evitar refactors masivos si no son parte del alcance.
- No romper contratos públicos existentes (hooks, filtros, nombres de opciones/meta, endpoints, shortcodes).
- Minimizar side effects en carga global de WordPress.

## 2) Estándares de Código PHP 8+

- Seguir WordPress Coding Standards (WPCS) y PSR-12 cuando no haya conflicto.
- Usar `declare(strict_types=1);` en código nuevo desacoplado de APIs WP (utilidades, servicios internos).
- Aprovechar tipado moderno cuando sea compatible:
- type hints en parámetros y retornos.
- propiedades tipadas en clases nuevas.
- `?Type`, `Type|Type2` solo cuando aporten claridad real.
- Evitar lógica compleja en funciones largas; extraer métodos privados con nombres explícitos.
- Evitar estado global mutable. No usar `static` para cachear salvo justificación de rendimiento.
- Manejar errores explícitamente:
- usar `WP_Error` para flujos WP/WC cuando aplique.
- no silenciar errores con `@`.

## 3) Buenas Prácticas WordPress

- Seguridad obligatoria:
- validar capacidades con `current_user_can()`.
- verificar nonce en acciones POST/AJAX (`check_admin_referer`, `check_ajax_referer`).
- sanitizar entrada al guardar (`sanitize_text_field`, `absint`, `wp_kses_post`, etc.).
- escapar salida al renderizar (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`).
- Hooks:
- registrar hooks en un punto de bootstrap claro.
- callbacks con responsabilidad única.
- documentar hooks personalizados con PHPDoc.
- Base de datos:
- usar `$wpdb->prepare()` siempre en SQL dinámico.
- preferir APIs WP (`get_post_meta`, `update_option`, `wp_insert_post`, etc.) sobre SQL directo.
- i18n:
- todas las cadenas visibles con `__()`, `_e()`, `esc_html__()`, etc.
- mantener text domain consistente del plugin.

## 4) Buenas Prácticas WooCommerce

- No modificar core de WooCommerce ni templates del plugin de terceros directamente.
- Integrar mediante APIs/hook oficiales de WooCommerce.
- Usar objetos de dominio WC (`WC_Product`, `WC_Order`, helpers) antes que acceder metadatos "a mano".
- Para producto/checkout/order:
- respetar validaciones y lifecycle nativo de WooCommerce.
- no romper compatibilidad con HPOS ni con flujos de stock/precio.
- Si se personalizan templates:
- mantener override en rutas del plugin/tema correspondiente.
- documentar versión de referencia del template y revisar diffs en upgrades.

## 5) Arquitectura Recomendada (Plugins)

Organizar el código por capas y responsabilidades, siguiendo la "Scream Archecture"

```text
cm-ecommerce/
├── Modules/
│   ├── ProductTypes/
│   │   ├── TorneoPoker/
│   │   │   ├── Domain/
│   │   │   │   ├── Entity/
│   │   │   │   │   └── class-cm-product-torneo-poker.php
│   │   │   │   ├── ValueObject/
│   │   │   │   │   ├── class-cm-torneo-poker-list.php
│   │   │   │   ├── Repository/
│   │   │   │   │   └── interface-cm-product-torneo-poker-repository.php
│   │   │   │   └── Service/
│   │   │   │       └── class-cm-product-torneo-poker-domain-service.php
│   │   │   ├── Application/
│   │   │   │   ├── UseCase/
│   │   │   │   │   └── ListProductTorneoPoker/
│   │   │   │   │       └── class-cm-list-product-torneo-poker-use-case.php
│   │   │   │   ├── DTO/
│   │   │   │   │   ├── class-cm-create-torneo-poker-request.php
│   │   │   │   │   └── class-cm-torneo-poker-response.php
│   │   │   │   └── Handler/
│   │   │   │       └── class-cm-torneo-poker-command-handler.php
│   │   │   ├── Infrastructure/
│   │   │   │   ├── Persistence/
│   │   │   │   │   └── class-cm-wc-torneo-poker-repository.php
│   │   │   │   ├── Wordpress/
│   │   │   │   │   ├── class-cm-torneo-poker-hooks.php
│   │   │   │   │   ├── class-cm-torneo-poker-post-type.php
│   │   │   │   │   └── class-cm-torneo-poker-meta-boxes.php
│   │   │   │   └── Provider/
│   │   │   │       └── class-cm-torneo-poker-service-provider.php
│   │   │   └── UI/
│   │   │       ├── Admin/
│   │   │       │   ├── Controller/
│   │   │       │   │   └── class-cm-admin-torneo-poker-controller.php
│   │   │       │   └── View/
│   │   │       │       └── torneo-poker-settings.php
│   │   │       └── Front/
│   │   │           ├── Controller/
│   │   │           │   └── class-cm-front-torneo-poker-controller.php
│   │   │           └── View/
│   │   │               └── single-torneo-poker.php
│   │   ├── Evento/
│   │   │   ├── Domain/
│   │   │   ├── Application/
│   │   │   ├── Infrastructure/
│   │   │   └── UI/
│   │   └── [CustomProductType]/
│   │       ├── Domain/
│   │       ├── Application/
│   │       ├── Infrastructure/
│   │       └── UI/
│   │
│   ├── Inscripciones/
│   │   ├── Domain/
│   │   │   ├── Entity/
│   │   │   │   └── class-cm-inscripcion.php
│   │   │   ├── ValueObject/
│   │   │   │   ├── class-cm-inscripcion-id.php
│   │   │   │   └── class-cm-estado-inscripcion.php
│   │   │   └── Repository/
│   │   │       └── interface-cm-inscripcion-repository.php
│   │   ├── Application/
│   │   │   └── UseCase/
│   │   │       ├── CrearInscripcion/
│   │   │       ├── CancelarInscripcion/
│   │   │       └── ListarInscripciones/
│   │   ├── Infrastructure/
│   │   │   └── Persistence/
│   │   │       └── class-cm-wp-inscripcion-repository.php
│   │   └── UI/
│   │       ├── Admin/
│   │       └── Front/
│   │
│   ├── Pagos/
│   │   ├── Domain/
│   │   ├── Application/
│   │   ├── Infrastructure/
│   │   └── UI/
│   │
│   └── Shared/
│       ├── Domain/
│       │   ├── ValueObject/
│       │   ├── Bus/
│       │   └── Exception/
│       ├── Infrastructure/
│       │   ├── Container/
│       │   ├── Persistence/
│       │   └── Logger/
│       └── UI/
│           └── Helpers/
│
├── bootstrap/
│   ├── class-cm-autoloader.php
│   ├── class-cm-container.php
│   └── class-cm-plugin-bootstrap.php
│
├── config/
│   ├── services.php
│   └── modules.php
│
├── tests/
│   ├── Unit/
│   ├── Integration/
│   └── Functional/
│
├── package.json
├── composer.json
└── README.md
```

Reglas:

- Evitar clases "god class".
- Inyección de dependencias simple por constructor cuando sea viable.
- Mantener compatibilidad con convención existente del plugin tocado (por ejemplo prefijos de clase).
- Cada clase debe tener una única responsabilidad principal.

## 6) Frontend (WP)

- Encolar assets con `wp_enqueue_script/style` y versionado (`filemtime` en dev, versión fija en release).
- No insertar JS/CSS inline salvo necesidad clara.
- Mantener HTML semántico y accesible (atributos `aria-*`, labels, contraste).
- Evitar depender de jQuery si no es necesario.

## 7) Rendimiento

- Evitar consultas repetidas en loops; cachear resultados cuando aplique (transients/object cache).
- Limitar carga condicional de hooks/assets a pantallas o contextos específicos.
- No ejecutar lógica pesada en cada request si puede diferirse (cron, actions asíncronas).

## 8) Compatibilidad y Migraciones

- Verificar compatibilidad con PHP 8.x y versión de WP/WC objetivo del proyecto.
- Al cambiar estructuras de datos/opciones/meta:
- crear migraciones idempotentes.
- conservar retrocompatibilidad cuando sea posible.
- En `uninstall.php`, eliminar solo datos propios del plugin y de forma segura.

## 9) Calidad, Testing y Verificación

Antes de cerrar un cambio:

- revisar sintaxis y tipos (`php -l` y/o análisis estático si está disponible).
- comprobar flujo feliz y edge cases.
- verificar seguridad (nonce/capability/sanitize/escape).
- validar que no hay regresiones en hooks existentes.
- si aplica, añadir o actualizar tests (unitarios/integración).

Si no se puede ejecutar test localmente, documentar exactamente qué faltó validar.

## 10) Convención de Cambios para Agentes

- Explicar brevemente: qué se cambió, por qué, riesgos y cómo validar.
- Referenciar archivos editados con rutas claras.
- No incluir secretos ni credenciales en código o logs.
- No usar comandos destructivos de git sin autorización explícita.

## 11) Checklist Rápido (Definition of Done)

- [ ] Código legible, tipado y con responsabilidades claras.
- [ ] Seguridad WP correcta (capabilities, nonce, sanitización, escape).
- [ ] Integración WooCommerce vía APIs/hooks oficiales.
- [ ] Arquitectura coherente con el plugin y sin acoplamiento innecesario.
- [ ] Sin impacto negativo obvio en rendimiento.
- [ ] Validación funcional realizada o limitaciones documentadas.

