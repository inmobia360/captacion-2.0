# Puerta SEO para staging y sustitución de dominio

## Objetivo

Validar `snow-jellyfish-183518.hostingersite.com` sin que compita en buscadores
y sin sustituir `compracaptacion.com` hasta tener evidencia de migración.

## Staging

- [ ] `noindex, nofollow` o acceso protegido.
- [ ] Sin canonical hacia staging.
- [ ] Sin credenciales ni datos reales innecesarios.
- [ ] Se mantienen los límites público, Pro y CRM.

## Validación pública

- [ ] H1, title, descripción y canonical correctos por ruta.
- [ ] `robots.txt` y `sitemap.xml` XML válido del dominio final.
- [ ] CRM noindex y fuera del sitemap.
- [ ] Pro fuera del sitemap y sin contenido privado indexable.
- [ ] HTML significativo con JS desactivado.
- [ ] Imágenes con dimensiones y lazy-load cuando proceda.
- [ ] Enlaces, 404, redirecciones, schema y rendimiento móvil revisados.

## Antes de cambiar el dominio

- [ ] Inventario de URLs y tráfico de Search Console.
- [ ] Tabla origen → destino → código esperado.
- [ ] Backup y rollback.
- [ ] Smoke tests de registro, login, Pro y denegación de CRM.
- [ ] Revisión de oportunidades sensibles individuales.
- [ ] Autorización explícita del propietario.

## Bloqueos

No se sustituye el dominio si hay URLs críticas sin redirección, staging
indexable, canonicales incorrectos, CRM rastreable, datos sensibles públicos o
regresión del acceso.
