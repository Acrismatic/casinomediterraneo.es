# AGENTS.md

## Project Overview
Plugin de Wordpress para extender la funcionalidad de Woocommerce. El plugin añade tipos de producto: 
1. Tipo "Torneo póker"
2. Tipo "Evevento"

Cada uno de ellos es igual, con las mismas propiedades que un "Producto Simple Virtual" de Woocommerce, pero se añade un select con búscqueda:
- Torneo Poker: lista todos los torneo de poker
- Evento: Lista todos los eventos

## Project Structure
Explicación rápida del repositorio.

- /src → código principal
- /components → componentes UI
- /services → lógica de negocio
- /tests → tests

## Code Style
Convenciones obligatorias.

- Lenguaje principal es PHP, Javascript
- Lenguaje de marcado: HTML
- Estilos: CSS
- Convención de nombres: Nombres de variables, clases, funciones, métodos, etc; en inglés y DEBEN ser destrictivas
- Utilizar lo medada de los posible patrones SOLID
- Usar PREFERENTEMENTE métodos, hooks, actions, etc de Wordpress y Woocommerce frente a crearlos.
- Usar la skill `woocommerce-backend-dev` SIEMPRE que vayas a hacer algo relacionado con Woocommerce y productos

## Git Workflow
Normas para commits y PR.

- Commits: conventional commits

## Agent Rules
Reglas específicas para el agente.

- No modificar archivos de configuración sin permiso
- No añadir dependencias nuevas sin justificar
- Mantener funciones pequeñas y modulares

## Boundaries
Limitaciones claras.

- No modificar `/understrap`
- No modificar `/understrap-child`
- No modficar plugins, EXCEPTO `cm-ecommerce`
- No cambiar APIs públicas