# Clarificación QA — Spec 001

## Ambigüedades detectadas

1. “Identidad única” puede significar cookie compartida entre subdominios o
   intercambio de token entre aplicaciones. Son modelos con riesgos y costes distintos.
2. No está confirmado si `crm.compracaptacion.com` comparte sesión con el área
   profesional o mantiene una autenticación Staff independiente.
3. No está confirmado qué permisos distinguen `agency`, `premium`, `admin`,
   `staff` y `master_admin` en producción.
4. La autoridad de sesión no está demostrada solo por la estructura local del
   código; debe comprobarse mediante configuración y comportamiento desplegado.

## Riesgos que deben resolverse antes del plan

- Compartir una cookie demasiado amplia entre subdominios podría aumentar el
  impacto de una vulnerabilidad en una superficie.
- Un token de intercambio mal diseñado podría permitir reutilización o escalado
  de privilegios.
- Mantener autenticaciones duplicadas produciría cuentas y revocaciones inconsistentes.
- La transición podría cerrar sesiones activas o dejar rutas privadas sin protección.

## Veredicto

La spec es estructurable, pero no está lista para implementación. Requiere
confirmar primero el modelo real de sesiones, el dominio de autoridad y la matriz
de roles.
