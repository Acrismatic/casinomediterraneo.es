# AGENTS.md (Root Index)

Este archivo es un indice de agentes del repositorio.

## Como usar este indice

- Empieza siempre por este archivo.
- Despues, aplica el `AGENTS.md` mas cercano al area del codigo que vayas a editar.
- Regla de prioridad: `mas especifico > mas general`.

## Indice de AGENTS locales

- Plugin `cm-ecommerce`:
	- `#file:src/wp-content/plugins/cm-ecommerce/AGENTS.md`
	- Ruta: `src/wp-content/plugins/cm-ecommerce/AGENTS.md`

## Convencion para nuevos AGENTS

- Si un modulo necesita reglas propias, crear su `AGENTS.md` en la raiz de ese modulo.
- Mantener este indice actualizado agregando una nueva entrada por cada `AGENTS.md` creado.
- No duplicar reglas largas en el root: el detalle vive en cada agente local.

## Alcance del root

- Este archivo define navegacion y precedencia.
- Las practicas tecnicas (PHP 8, WordPress, WooCommerce, arquitectura) deben mantenerse en los AGENTS especificos del modulo.
