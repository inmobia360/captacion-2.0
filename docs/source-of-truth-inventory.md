# Inventario de fuente de verdad — 2026-08-31

## Veredicto provisional

`CompraCaptacion/` queda designada como **fuente operativa candidata** para la
aplicación principal. El repositorio premium objetivo para integrar es
`https://github.com/inmobia360/compracaptacion.premium`. Esta decisión aún debe
confirmarse contra la versión realmente servida en producción y contra el
contenido accesible del repositorio remoto.

## Evidencia comparativa

| Ubicación | Estado observado | Evidencia |
|---|---|---|
| `CompraCaptacion/` | Implementación más completa y recientemente modificada | 280 archivos; contiene endpoints adicionales, tests JS y documentación reciente |
| `CompraCapracion_Premium/Compra Captación/repo/` | Snapshot con historial Git independiente | 328 archivos; remoto `compracaptacion_antigravity`; último commit local `2ef0e94` |
| `CompraCapracion_Premium/github-premium-repo/` | Variante estática/premium reducida | 23 archivos; no contiene el backend completo |
| `CompraCapracion_Premium/Compra Captación/compracaptacion-premium/` | Prototipo o landing estática | 49 archivos; no contiene el backend completo |
| `https://github.com/inmobia360/compracaptacion.premium` | Repositorio premium remoto objetivo | Debe auditarse y compararse antes de integrar |

## Diferencias relevantes

`CompraCaptacion/` contiene, frente al snapshot Git anidado:

- `api/dossiers.php`
- `api/matches.php`
- `api/reputation.php`
- `api/admin/backup.php`
- `tests/validate_syntax.js`
- `docs/13-definiciones-metricas-panel-ejecutivo.md`
- `docs/14-referencia-produccion-2026-08-24.md`
- `pnpm-lock.yaml`

Los archivos centrales no son idénticos: `index.php`, `api/auth.php`,
`api/credits.php`, `api/stripe.php`, `crm/index.php` y los bundles JavaScript
tienen hashes distintos. No debe realizarse una copia ciega entre ambas
ubicaciones.

## Reglas de transición

1. No borrar, mover ni sobrescribir ninguna variante hasta confirmar la fuente de producción.
2. No tratar el repositorio Git anidado como fuente activa solo por tener historial.
3. Comparar comportamiento y configuración, no únicamente nombres o tamaños de archivo.
4. Toda funcionalidad que se quiera conservar debe tener una spec antes de una futura fusión.
5. El repositorio raíz actual sigue sin commits; esta fase no autoriza commit ni push.

## Decisión pendiente

Confirmar si `CompraCaptacion/` corresponde al código desplegado actualmente,
auditar el contenido de `inmobia360/compracaptacion.premium` y registrar la
estrategia de integración antes de reorganizar el árbol.
