# PLANTILLA DE ESTÁNDARES DE DESARROLLO, ORQUESTACIÓN DE AGENTES Y AUTOMATIZACIÓN SEGURA
## [PROYECTO: NOMBRE_DEL_PROYECTO_TEMPORAL]

Esta plantilla establece los estándares de ingeniería de software, arquitectura de repositorios, políticas de orquestación de agentes de inteligencia artificial y auditoría de seguridad para futuros despliegues de productos digitales de [NOMBRE_EMPRESA].

---

### SECCIÓN 1: ESTÁNDARES DEL ENTORNO DE DESARROLLO Y STACK TECNOLÓGICO

Cualquier proyecto futuro debe seguir una arquitectura moderna asistida por IA y ejecutarse bajo un entorno completamente parametrizado.

#### 1. Stack de Tecnologías Estándar
*   **Entorno de Ejecución**: Node.js v[VERSION_RECOMENDADA] o superior.
*   **Gestión de Versiones**: Git para control de código fuente distribuido.
*   **Editor Recomendado**: VS Code o IDEs optimizados para IA (Cursor, Antigravity IDE, Claude Code). [27]
*   **Infraestructura de Despliegue**: Servidor Virtual Privado (VPS) administrado mediante **Dokploy** (orquestador de contenedores auto-alojado) en [PROVEEDOR_VPS, ej. Hostinger]. [36]

#### 2. Gestión de Dependencias Segura: Uso Obligatorio de `pnpm`
*   Es **estrictamente obligatorio** utilizar `pnpm` en lugar de `npm` o `yarn` en todos los subproyectos. [39, 41]
*   **Justificación de Ciberseguridad**: Se ha detectado que `npm` presenta riesgos de mitigación de paquetes maliciosos que explotan estados y dependencias fantasma. `pnpm` mitiga este vector gracias a su estructura de almacén único direccionable por contenido y enlazado físico, garantizando un árbol de dependencias estricto y aislado, evitando la inyección lateral de librerías comprometidas. [41]

#### 3. Regla de Oro de Git y Repositorios
*   **POLÍTICA ESTRICTA**: El desarrollador o agente de IA autónomo **NUNCA** debe subir cambios directos a producción ni ejecutar comandos de subida (`git add`, `git commit`, `git push`) a repositorios compartidos sin **autorización explícita y manual** del Director del Proyecto. Esta regla debe estar codificada en las reglas de sistema de los agentes de codificación. [36]

---

### SECCIÓN 2: ESTRUCTURA ORGANIZATIVA DEL REPOSITORIO (ARCHITECTURE-AS-CODE)

El proyecto debe mantener una jerarquía de carpetas estricta para que cualquier agente de IA pueda entender instantáneamente la arquitectura del software. [33]

```text
[proyecto]/
├── .agents/
│   └── agents/
│       └── [nombre_agente].md         # Definición de Custom Agents
├── docs/
│   ├── architecture.md               # Arquitectura del sistema y flujo de datos
│   ├── current-state.md              # Estado actual de tareas y roadblocks
│   └── decisions/                    # Registros de Decisiones Arquitectónicas (ADRs)
│       ├── 001-database.md           # Elección de base de datos y esquemas
│       ├── 002-authentication.md     # Estrategia de autenticación y roles
│       └── ...
├── src/                              # Código fuente de la aplicación
├── tests/                            # Batería de pruebas automatizadas
├── AGENTS.md                         # Reglas de comportamiento globales de agentes (Symmetric)
├── CLAUDE.md                         # Duplicado idéntico para herramientas específicas de Anthropic
├── README.md                         # Documentación general de instalación y configuración
├── package.json
└── pnpm-lock.yaml
```

#### Regla de Sincronización de Contexto de Agentes:
*   `AGENTS.md` y `CLAUDE.md` deben ser **exactamente idénticos** y contener las mismas directrices de desarrollo, variables y límites de seguridad. [33]
*   **Mecanismo de Sincronización Obligatorio**: Se exige configurar un hook de Git o un script de automatización que sincronice automáticamente ambos archivos siempre que se realice una modificación en cualquiera de ellos. [33]
*   **Documentación Continua**: Cada cambio sustancial en la arquitectura o en el estado del software debe reflejarse inmediatamente en la carpeta `docs/` y actualizar el archivo `current-state.md`. [33]

---

### SECCIÓN 3: ORQUESTACIÓN DE AGENTES Y SKILLS REUTILIZABLES

Aprovechando el ecosistema de orquestación moderno, el desarrollo se delegará en agentes especializados, limitando la sobrecarga del contexto (Context Bloat). [68, 69]

#### 1. Configuración de Custom Agents en `.agents/agents/`
Cada agente especialista (ej. experto en bases de datos, maquetador CSS) se define con un archivo Markdown (`.md`) con cabecera YAML bajo el estándar de orquestación: [72, 73]

```markdown
---
name: [nombre-del-agente-especialista]
description: [Explicación clara de qué hace para que el coordinador lo llame]
model: [ej. flash / claude-3-5-sonnet]
mainAgent: true                       # Permite invocarlo directamente por CLI o GUI
subagent: true                       # Permite que un agente coordinador lo delegue
permissionMode: acceptEdits           # Modo de permisos para cambios de archivos
commandExecutionPolicy: auto          # Ejecución autónoma de tests/compilaciones de fondo
tools:
  - view_file
  - replace_file_content
  - run_command
skills:
  - [ruta_o_identificador_de_skill]
---

# Instrucciones Principales
Eres un agente especialista en [ÁREA]. Tu misión es...
```

*   **Simetría de Ejecución**: La habilitación conjunta de `mainAgent: true` y `subagent: true` asegura que el especialista funcione tanto de interfaz principal directa para el desarrollador, como de subagente delegado de forma autónoma. [75]
*   **Políticas de Seguridad Acotadas**: Al configurar `commandExecutionPolicy: auto` con `permissionMode: acceptEdits`, el agente ejecuta ciclos de prueba y compilaciones de fondo de manera autónoma, agilizando el feedback sin interrumpir al desarrollador con constantes solicitudes de confirmación, limitando la autorización manual solo a comandos críticos de destrucción o subida. [76, 77]
*   **Prevención de Tool Confusion**: Solo se le asignan al agente las herramientas (`tools`) y habilidades (`skills`) estrictamente necesarias para su rol, evitando la saturación del contexto de trabajo. [78]

#### 2. Incorporación de Habilidades Reutilizables (Skills CLI)
*   Para ampliar las capacidades procedimentales del agente sin saturar el sistema, se deben instalar habilidades modulares del directorio de habilidades abiertas utilizando el comando CLI: [82]
    `npx skills add <owner/repo>`
    *(Ejemplo: `npx skills add anthropics/skills --skill frontend-design` o `npx skills add supabase/agent-skills --skill supabase-postgres-best-practices`)*. [34, 94]

---

### SECCIÓN 4: PLANTILLA DE AUDITORÍA DE SEGURIDAD (LAS PAUTAS DE ÉXITO)

Ningún entregable o versión de software se considerará en estado de **"ÉXITO"** ni apto para producción si no cumple rigurosamente con los siguientes 11 criterios no negociables. [37]

#### Checklist de Ciberseguridad Obligatorio:
1.  **Variables de Entorno**: Ninguna credencial, token o contraseña hardcodeada. Archivo `.env` referenciado en `.gitignore` y archivo `.env.example` actualizado con variables ficticias. [37]
2.  **Validación de Entradas (Inputs)**: Sanitización activa de todas las entradas del cliente para prevenir inyecciones SQL y ataques Cross-Site Scripting (XSS). [37]
3.  **Aislamiento de Base de Datos**: No realizar conexiones directas a bases de datos desde el frontend. Al usar soluciones como Supabase, se exige habilitar políticas estrictas de **Row Level Security (RLS)** para cada tabla. [38]
4.  **Autenticación de Rutas**: Control y restricción estricta de rutas privadas. Bloquear el acceso de usuarios no autenticados a datos protegidos. [38]
5.  **Control de Roles en el Servidor (RBAC)**: Validar permisos y niveles de acceso obligatoriamente en el servidor/Backend, nunca confiar exclusivamente en validaciones visuales del frontend. [38]
6.  **Whitelists de CORS**: Configurar cabeceras de CORS restrictivas. La API solo debe permitir solicitudes de dominios autorizados de forma explícita. [38]
7.  **Rate Limiting**: Mitigación de fuerza bruta y ataques DoS mediante límites de solicitudes por IP o token de API. [39]
8.  **Manejo Seguro de Errores**: Ocultar trazas internas, rutas físicas de archivos o versiones de base de datos al usuario final en las respuestas de error. [39]
9.  **Uso Obligatorio de `pnpm`**: Estándar de dependencias que evita paquetes maliciosos y de estado lateral. [39]
10. **Auditoría de Dependencias**: Ejecución periódica de análisis de seguridad en librerías de terceros (ej. `pnpm audit` o similares). [39]
11. **Exclusión de Datos Sensibles en Logs**: Asegurar que datos sensibles de usuarios o tokens de autorización nunca se impriman en el syslog ni servicios de monitorización. [39]

#### PROMPT MAESTRO: Auditoría Autónoma de Ciberseguridad
Antes de realizar cualquier despliegue, el desarrollador o agente de IA debe ejecutar textualmente el siguiente prompt de auditoría sobre el repositorio: [40]

```text
Actúa como un experto en ciberseguridad especializado en aplicaciones web.
Analiza todo el código de este proyecto e identifica TODOS los problemas
de seguridad existentes, sin excepción.
Para cada problema encontrado, indícame:
1. Qué es el problema exactamente
2. Dónde está en el código (archivo y línea si es posible)
3. Qué riesgo concreto supone si no se soluciona
4. Cómo solucionarlo, con el código corregido listo para aplicar

Revisa obligatoriamente estas áreas:
- Trata de usar pnpm en lugar de npm cuando sea posible: npm puede descargar librerías con estado malicioso como se ha descubierto recientemente. Por lo que cambia el sistema para usar siempre que sea posible pnpm y déjalo anotado en el proyecto para que en futuro se tenga en cuenta.
- Variables de entorno y exposición de claves API
- Validación y sanitización de inputs del usuario
- Protección contra SQL Injection y XSS
- Autenticación y gestión de sesiones
- Control de acceso y permisos (roles, RLS en base de datos)
- Exposición innecesaria de datos sensibles en respuestas de la API
- Dependencias desactualizadas o con vulnerabilidades conocidas
- Headers de seguridad HTTP
- Rate limiting y protección contra fuerza bruta
- Configuración de CORS
- Manejo seguro de errores (que no expongan información interna)
- Datos sensibles en logs

Al terminar, dame un resumen con:
- Total de problemas encontrados
- Cuáles son críticos y hay que solucionar YA
- Cuáles son importantes pero no urgentes
- Cuáles son mejoras opcionales
Sé directo. No me expliques teoría, dame los problemas reales de ESTE código y cómo arreglarlos.
``` [40, 41, 42, 43]

---

### SECCIÓN 5: ARQUITECTURA DE INTEGRACIONES Y AUTOMATIZACIÓN SEGURA (N8N)

El flujo estandarizado de leads o eventos desde el frontend de captación hacia el almacenamiento persistente debe seguir una arquitectura desacoplada y robusta en n8n: [61]

1.  **Webhook de Recepción Seguro**:
    *   El frontend envía la carga de datos mediante una solicitud POST a un webhook de n8n. [62]
    *   **Seguridad**: Validar que la cabecera contenga un token o clave secreta de verificación. Si no coincide, el flujo se aborta de inmediato (`401 Unauthorized`). [62]
2.  **Identificación y Vinculación de Clientes Preexistentes**:
    *   Búsqueda exacta en la tabla de clientes de la base de datos (Supabase) por teléfono y correo electrónico. [62]
    *   Si se encuentra coincidencia, el lead se asocia al ID del cliente existente para unificar el historial comercial. [63]
3.  **Clasificación de Prioridad por Lógica de Negocio (Sin IA)**:
    *   La prioridad se calcula bajo reglas lógicas deterministas: [62]
        *   **Alta**: Solicitudes de máxima urgencia (ej. avería/reparación inmediata) o que el plazo requerido de implementación sea "Este mes". [62]
        *   **Media**: Solicitudes de mantenimiento programado o plazos de instalación de "Próximos meses". [62]
        *   **Baja**: Consultas meramente informativas o sin un plazo temporal urgente. [63]
4.  **Orquestación de IA para Resumen y Detección de Urgencia Oculta**:
    *   Si el usuario ingresó comentarios en el formulario, se activa un nodo de IA de n8n (usando **OpenRouter** con el modelo **Gemini Flash**). [63, 65]
    *   La IA genera un resumen en una sola línea del requerimiento y analiza si el tono del mensaje denota una urgencia oculta no contemplada en los campos estándar (ejemplo: incidentes críticos o fallos catastróficos que requieran escalado). [63]
5.  **Inserción Persistente Desacoplada**:
    *   Registrar la solicitud en una tabla de `leads` separada de la de clientes de Supabase (nombre, teléfono, email, prioridad calculada, resumen de IA, ID de cliente vinculado, estado inicial: `Nuevo`). [59, 60, 63]
6.  **Disparador de Notificación / Email de Confirmación**:
    *   Enviar correo electrónico personalizado basándose en el tipo de solicitud. [63]
    *   **Reglas de Redacción**:
        *   Confirmar la recepción de la solicitud y establecer el plazo comercial de llamada de seguimiento. [64]
        *   **Prohibido**: Dar presupuestos estimativos o comprometerse a fechas exactas en este correo automatizado. [64]
        *   **Personalización**: Si se identificó como cliente preexistente, cambiar el tono de voz para dar un trato personalizado de alta fidelidad. [64]
7.  **Respuesta Rápida Asíncrona (Optimización UX)**:
    *   El webhook de n8n debe responder exitosamente (`Status 200/201`) al frontend inmediatamente después de validar la seguridad del webhook, sin esperar la finalización del envío del correo electrónico o del procesamiento de IA. Esto garantiza tiempos de respuesta del sitio web extremadamente rápidos. [64]
8.  **Gestión Centralizada de Credenciales**:
    *   Todas las credenciales de base de datos, APIs de correo y tokens de OpenRouter deben gestionarse mediante el administrador de credenciales nativo de n8n. **Queda terminantemente prohibido hardcodear contraseñas o endpoints sensibles dentro de los nodos del flujo de trabajo**. [64]
