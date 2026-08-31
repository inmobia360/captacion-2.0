# Transición de fuente y despliegue

## Flujo oficial

```text
D:\COMPRACAPTACION 2.0
        ↓ fuente validada
D:\CAPTACION-2.0
        ↓ proyecto de trabajo y preparación
GitHub: inmobia360/captacion-2.0
        ↓ versión aprobada
Hostinger: snow-jellyfish-183518.hostingersite.com
```

## Reglas

- `D:\COMPRACAPTACION 2.0` es la referencia; no se modifica como efecto lateral del trabajo en el nuevo proyecto.
- `D:\CAPTACION-2.0` es la única carpeta de trabajo para las siguientes iteraciones.
- GitHub recibe únicamente cambios revisados y autorizados.
- Hostinger solo recibe una versión validada, con backup y rollback definidos.
- No se sincronizan secretos, bases de datos locales, logs ni archivos temporales.
