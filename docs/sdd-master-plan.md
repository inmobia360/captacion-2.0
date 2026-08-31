# SDD maestro — Compra Captación unificada

## Objetivo

Gestionar `compracaptacion.com`, `pro.compracaptacion.com` y
`crm.compracaptacion.com` como un único producto, con una estructura de
desarrollo común, límites claros entre superficies y trazabilidad desde la
necesidad de negocio hasta la prueba y el despliegue.

## Modelo objetivo

```text
Compra Captación — producto unificado
├── Superficie pública
│   └── adquisición, marketplace, recursos y conversión
├── Superficie profesional
│   └── acceso premium avanzado, full_tools y asistencia IA
├── Superficie CRM
│   └── operaciones internas, soporte, administración y auditoría
├── Núcleo compartido
│   ├── identidad y sesiones
│   ├── usuarios, roles y permisos
│   ├── captaciones, demandas y matching
│   ├── operaciones y acuerdos
│   ├── créditos, pagos y ledger
│   └── privacidad, auditoría y consentimientos
└── Contratos
    ├── API
    ├── datos
    ├── eventos/webhooks
    └── despliegue
```

## Principio de integración

Los tres dominios son superficies del mismo producto, no tres aplicaciones con
reglas duplicadas. El núcleo compartido es la autoridad. Cada dominio puede
tener su interfaz y permisos de presentación, pero no puede redefinir usuarios,
roles, saldo, pagos, estados contractuales ni datos protegidos.

## Estructura SDD objetivo

```text
CompraCaptacion/
├── docs/
│   ├── constitution.md
│   ├── sdd-master-plan.md
│   ├── production-context-YYYY-MM-DD.md
│   ├── architecture.md
│   ├── current-state.md
│   ├── decisions/
│   ├── contracts/
│   └── specs/
│       └── NNN-funcionalidad/
│           ├── spec.md
│           ├── clarification.md
│           ├── plan.md
│           ├── tasks.md
│           └── validation.md
├── public/       # superficie pública, cuando se extraiga sin riesgo
├── professional/ # superficie pro/premium, cuando se extraiga sin riesgo
├── crm/          # superficie CRM
├── core/         # reglas y servicios compartidos
├── api/          # contratos y endpoints del núcleo
├── assets/
├── tests/
└── .agents/
```

La extracción física de carpetas queda para una fase posterior. Primero se
documentarán contratos y pruebas para no romper el despliegue actual.

## Flujo único de trabajo

1. Constitución: límites y principios comunes.
2. Estado actual: comportamiento realmente desplegado.
3. Spec: qué problema se resuelve y qué debe ocurrir.
4. Clarificación: ambigüedades, riesgos y casos límite.
5. Plan: módulos, contratos, datos y pruebas.
6. Tareas: unidades pequeñas, cada una con RF y `Hecho cuando:`.
7. Implementación: una tarea por ciclo, tests primero.
8. Validación: recorrido RF por RF en las tres superficies afectadas.
9. Despliegue: solo tras aprobación y comprobación de regresión.

## Primeras specs del producto unificado

1. Identidad, sesión y autorización entre dominios.
2. Contrato de usuario, plan, entitlement y permisos.
3. Marketplace, captaciones, demandas y datos ciegos.
4. Créditos, consumo, expiración y ledger.
5. Stripe, pagos e idempotencia.
6. Operaciones, acuerdos y reparto 50/50.
7. API de integración Premium.
8. CRM, soporte y auditoría.
9. Métricas y panel ejecutivo.
10. Contratos de despliegue y regresión entre dominios.

## Criterio de éxito

El proyecto se considerará unificado cuando una funcionalidad pueda trazarse así:

`objetivo → spec → RF → contrato → código → test → validación → release`

y cuando los tres dominios compartan identidad, reglas de negocio y controles de
seguridad sin duplicar fuentes de verdad.
