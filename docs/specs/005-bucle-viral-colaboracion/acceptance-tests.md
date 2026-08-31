# Pruebas de aceptación — Spec 005

- Un enlace compartido muestra zona aproximada, tipo, precio/presupuesto y requisitos clave.
- El enlace no muestra dirección exacta, catastro, contacto, documentos ni identificadores internos.
- Un visitante puede leer la ficha sin registrarse.
- El CTA de compatibilidad conserva `share_token` y `record_id` lógico durante el registro.
- Un usuario no autenticado es llevado al match original tras completar el acceso.
- Un enlace revocado o caducado no permite iniciar colaboración.
- Dos clics o reintentos no duplican recompensa.
- Un registro sin verificación o sin oportunidad válida no genera créditos.
- CRM no es accesible desde la ficha pública ni desde el flujo de usuarios.
- Los eventos no contienen PII innecesaria ni datos sensibles de la oportunidad.
