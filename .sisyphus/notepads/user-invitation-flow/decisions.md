# Decisions - User Invitation Flow

This file tracks architectural choices and technical decisions made during implementation.

---

## Pre-Implementation Decisions (from Planning)

- **Signed URLs over DB tokens**: Using Laravel's `URL::temporarySignedRoute()` with 48h expiry — stateless, no extra tables
- **Null password approach**: User created with `password` column NULL (omitted from `User::create()` attributes) until invitation accepted
- **Status derivation**: Invitation status derived from `password IS NULL` — no new columns, no state sync issues
- **Notification dispatch**: Dispatched from controller (not service) — services must not have side effects
- **Invokable controller pattern**: Two separate invokable controllers for acceptance (ShowAcceptInvitationController, StoreAcceptInvitationController) matching existing codebase pattern

---
