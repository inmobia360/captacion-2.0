---
name: api-design-principles
description: >-
  RESTful and GraphQL API design best practices, idempotency, versioning,
  secure authentication, payload sanitization, and OpenAPI contracts.
---

# API Design Principles Skill

## Standards
1. **Predictable URIs**: Resource-oriented naming (e.g. `/wp-json/captacion/v1/captaciones`).
2. **Standard HTTP Statuses**: 200 OK, 201 Created, 400 Bad Request, 401 Unauthorized, 403 Forbidden, 422 Unprocessable Entity.
3. **Security & Nonces**: Mandatory nonce / JWT verification on all mutating endpoints (POST, PUT, DELETE).
4. **Idempotency**: Use `Idempotency-Key` headers on financial and credit operations.
