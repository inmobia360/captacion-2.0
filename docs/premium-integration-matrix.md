# Matriz de integración premium — borrador SDD

## Objetivo

Integrar las capacidades premium en un único proyecto funcional sin duplicar
usuarios, permisos, datos sensibles ni reglas financieras.

## Principio de arquitectura

La aplicación principal conserva la autoridad sobre usuarios, roles, permisos,
captaciones, demandas, créditos, ledger y operaciones. Las capacidades premium
se integran como módulos delimitados y contratos versionados. No se copiarán
credenciales, bases de datos, datos de producción ni direcciones protegidas.

| Capacidad | Fuente premium identificada | Encaje en CompraCaptacion | Tratamiento SDD | Estado |
|---|---|---|---|---|
| Dossier Premium | `docs/contexto-negocio.md`, `api/dossiers.php` existente | Reutilizar datos autorizados de captación y operación | Spec independiente + contrato de datos | Candidato |
| Matching avanzado | `docs/contexto-intelligence.md`, `api/matches.php` existente | Extender matching sin alterar reglas básicas | Spec de matching y trazabilidad | Candidato |
| Reputación / verificación | `api/reputation.php` existente | Integrar con perfil y señales verificables | Spec de reputación y privacidad | Candidato |
| Radar de oportunidades | `docs/contexto-intelligence.md` | Módulo premium con fuentes permitidas | Spec de oportunidades y alertas | Candidato |
| Valoración ACM | `docs/contexto-intelligence.md` | Cálculo orientativo, nunca tasación oficial | Spec de valoración y fuentes | Candidato |
| Inteligencia territorial | `docs/contexto-intelligence.md`, territorios existentes | Reutilizar territorios y etiquetar estimaciones | Spec territorial y métricas | Candidato |
| Operaciones Pro | `docs/contexto-negocio.md` | Extender pipeline existente respetando valores contractuales | Spec de operaciones premium | Candidato |
| Landing / hub premium | `docs/landing-hub-herramientas.md` | Nueva superficie del panel privado | Spec UX y estados de disponibilidad | Candidato |

## Límites obligatorios

- El premium no accede directamente a tablas internas mediante credenciales SQL.
- La autenticación premium debe usar sesión o token de intercambio temporal, nunca contraseñas duplicadas.
- Cada integración debe declarar campos, sensibilidad, permisos, versionado,
  idempotencia, reintentos, auditoría y retención.
- Las estimaciones deben mostrar fuente, fecha y nivel de confianza.
- Los cambios en créditos, pagos u operaciones pertenecen al núcleo principal.

## Orden de integración recomendado

1. Dossier Premium y contratos de datos.
2. Hub premium y control de disponibilidad por plan.
3. Matching avanzado y reputación.
4. Radar de oportunidades y alertas.
5. Valoración ACM e inteligencia territorial.
6. Automatizaciones y extensiones externas, después de validar legalidad y demanda.

## Pendiente de verificación remota

El contenido del repositorio remoto `inmobia360/compracaptacion.premium` no pudo
ser leído directamente en esta sesión. La matriz usa el snapshot local
`CompraCapracion_Premium/github-premium-repo/` como evidencia provisional; antes
de fusionar habrá que comparar commits, archivos y contratos del remoto.
