# Modelo de datos del diagnóstico de captación v1

## Clasificación

- `public`: puede aparecer en una publicación autorizada.
- `professional`: visible solo al profesional propietario del diagnóstico.
- `collaboration`: visible a colaboradores autorizados.
- `restricted`: requiere finalidad, permiso y auditoría reforzada.

## Campos

| Grupo | Campo | Origen | Sensibilidad |
|---|---|---|---|
| Identidad | número de titulares | propietario/profesional | professional |
| Identidad | capacidad para vender | declaración + verificación | restricted |
| Motivación | motivo de venta | propietario | professional |
| Plazo | urgencia y ventana temporal | propietario | professional |
| Inmueble | tipo, superficie, habitaciones, estado | profesional/propietario | public |
| Localización | municipio, zona, CP aproximado | profesional/territorial | public/professional |
| Precio | precio solicitado | propietario | public |
| Jurídico | hipoteca, cargas, arrendamiento, ocupación | propietario/documentación | restricted |
| Jurídico | herencia, usufructo, VPO | propietario/documentación | restricted |
| Documentación | existencia y vigencia de documentos | profesional/documento | professional/restricted |
| Mercado | comparables y métricas | fuente permitida | professional |
| Estrategia | escenario rápido, mercado, aspiracional | sistema/profesional | professional |
| Resultado | score y factores | sistema | professional |
| Auditoría | actor, timestamp, versión y cambios | sistema | restricted |

## Estados del diagnóstico

`draft` → `in_review` → `ready_for_publication` → `published`

Estados alternativos:

- `needs_information`: faltan datos críticos;
- `needs_expert`: requiere escalado profesional;
- `archived`: no activo.

## Reglas de datos

- El origen debe acompañar a cada dato importante.
- Los datos declarados no se presentan como verificados automáticamente.
- Los campos `restricted` no se incluyen en exports públicos ni en previews.
- La dirección exacta, catastro y contactos permanecen fuera de la publicación ciega.
- La retención debe depender de finalidad y base legal documentada.
- Los cambios relevantes deben conservar versión y actor, sin sobrescribir evidencia contractual.
