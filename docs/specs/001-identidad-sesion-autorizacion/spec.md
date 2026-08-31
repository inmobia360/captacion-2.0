# Spec 001 — Identidad, sesión y autorización entre dominios

## Contexto y objetivo

Las superficies pública, profesional y CRM forman un único producto, pero deben
compartir una identidad coherente sin duplicar contraseñas ni relajar los
permisos. Esta spec define el contrato mínimo para que cada dominio reconozca al
usuario correcto y aplique autorización server-side.

## Usuarios / actores

- Visitante no autenticado.
- Profesional inmobiliario autenticado.
- Usuario Staff del CRM.
- Administrador principal.
- Servicio interno autorizado entre dominios.

## Historias de usuario

- H1: Como profesional quiero registrarme o iniciar sesión y poder acceder a mi espacio permitido.
- H2: Como profesional con plan avanzado quiero acceder a `pro` sin crear otra cuenta y recibir solo las capacidades incluidas en mi plan.
- H3: Como Staff quiero acceder al CRM con permisos administrativos separados de los profesionales.
- H4: Como administrador quiero que cada acción sensible quede asociada a una identidad y un registro de auditoría.

## Requisitos funcionales (EARS)

- RF-1: CUANDO un usuario se autentique correctamente, EL SISTEMA debe crear una sesión identificable y devolver solo el perfil autorizado.
- RF-2: SI una petición no tiene una sesión válida, EL SISTEMA debe rechazar cualquier recurso privado o administrativo.
- RF-3: SI un usuario base o premium intenta acceder al CRM, EL SISTEMA debe denegar el acceso salvo que su rol Staff esté autorizado en backend.
- RF-3a: CUANDO un usuario intente acceder a `pro`, EL SISTEMA debe comprobar plan avanzado activo, estado de pago y capacidades concedidas antes de crear la sesión premium.
- RF-4: SI un usuario Staff intenta operar sobre una función no permitida por su categoría, EL SISTEMA debe rechazar la acción aunque la interfaz la muestre.
- RF-5: CUANDO una petición sensible se procese, EL SISTEMA debe registrar actor, acción, recurso, resultado y marca temporal sin almacenar contraseñas ni tokens.
- RF-6: CUANDO el usuario navegue entre dominios del producto, EL SISTEMA debe mantener una identidad única sin exigir contraseñas duplicadas.
- RF-7: SI una sesión expira, se revoca o no supera la validación de origen, EL SISTEMA debe solicitar autenticación de nuevo y no conceder acceso parcial.
- RF-8: EL SISTEMA debe separar la identidad del usuario de los datos sensibles de captaciones, propietarios y contactos.
- RF-9: SI un servicio interno se comunique entre superficies, EL SISTEMA debe validar autenticación del servicio, autorización, origen, versión de contrato e idempotencia cuando proceda.

## Requisitos no funcionales

- Contraseñas almacenadas únicamente mediante hash resistente.
- Cookies de sesión seguras, HttpOnly y con política SameSite adecuada al flujo validado.
- Protección CSRF para operaciones basadas en cookies.
- CORS restrictivo y cabeceras de seguridad en producción.
- Respuestas de error sin trazas, rutas físicas ni detalles de autenticación.
- Compatibilidad con PHP 8.x y la infraestructura actualmente desplegada.
- No romper las rutas públicas ni el panel profesional existente durante la migración.

## Casos límite

- Usuario autenticado en la web pública sin permisos profesionales.
- Usuario profesional pendiente de verificación.
- Usuario suspendido o eliminado.
- Sesión válida en un dominio pero revocada en el núcleo.
- Petición repetida durante un cambio de sesión.
- Cambio de rol mientras existen sesiones activas.
- Dominio premium temporalmente no disponible.
- CRM accesible por URL directa sin pasar por la interfaz.

## Fuera de alcance

- Cambiar ahora el proveedor de autenticación.
- Migrar contraseñas o sesiones en producción.
- Fusionar físicamente los tres despliegues.
- Rediseñar roles de negocio sin decisión aprobada.
- Compartir bases de datos directamente entre dominios premium y principal.

## Criterios de finalización

- El contrato de identidad y roles está documentado.
- Cada RF tiene al menos una prueba planificada.
- Se conoce qué dominio emite y valida la sesión.
- Se documentan expiración, revocación, CSRF, CORS y transferencia entre dominios.
- Existe un plan de migración reversible y sin cambio de producción incluido.

## Dudas abiertas

- [NECESITA VERIFICACIÓN] ¿Los tres dominios usan actualmente la misma base de usuarios y sesión?
- [NECESITA VERIFICACIÓN] ¿Qué dominio actúa hoy como autoridad de autenticación?
- [NECESITA DECISIÓN] ¿Se mantendrán sesiones compartidas por cookie o se adoptará un intercambio temporal de tokens?
- [NECESITA VERIFICACIÓN] ¿Qué roles y categorías Staff están activos actualmente?
