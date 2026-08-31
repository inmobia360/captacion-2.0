---
name: tdd
description: >-
  Test-Driven Development (TDD) principles, red-green-refactor workflows,
  unit testing, integration testing, and test automation best practices.
---

# Test-Driven Development (TDD) Skill

## Core Principles
1. **Red**: Write a failing test for the required functionality before writing implementation code.
2. **Green**: Write the minimal code necessary to make the test pass.
3. **Refactor**: Clean up the code while keeping all tests green.

## Rules & Verification
- Always isolate tests and mock external I/O (database, external APIs, Stripe webhooks).
- Maintain high code coverage on core domain logic (credit calculations, matching algorithm).
