# Verificación del despliegue de staging — 2026-08-31

## Configuración aplicada

- Repositorio: `inmobia360/captacion-2.0`
- Rama: `main`
- Destino: `public_html`
- Host: `snow-jellyfish-183518.hostingersite.com`
- Commit desplegado: `06994b5`

## Resultado

- `robots.txt`: HTTP 200, muestra una respuesta gestionada por
  Hostinger y no la salida esperada de `robots.php`.
- `sitemap.xml`: HTTP 200 y archivo presente.
- `/`: HTTP 200 tras la corrección de seeds.

## Estado

`RESUELTO — login anónimo controlado`

La raíz ya responde HTTP 200 tras la corrección de seeds. Los smoke tests
anónimos confirman que diagnósticos devuelve 401, CRM no concede sesión y los
endpoints públicos cargan. `api/auth.php?action=login` fallaba porque consultaba
`audit_logs` antes de que existiera la tabla. Tras añadirla, las credenciales
ficticias devuelven HTTP 200 con error controlado y cookie segura.

## Siguiente diagnóstico

1. Probar login válido con una cuenta de staging autorizada.
2. Probar logout y revocación de sesión.
3. Repetir smoke tests de raíz, robots, sitemap, API pública y CRM.
