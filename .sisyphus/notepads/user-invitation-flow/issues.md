# Issues - User Invitation Flow

This file tracks problems encountered and their resolutions.

---

## Known Gotchas (from Metis Review)

- **CRITICAL**: `users.password` column is NOT NULL in PostgreSQL — must migrate to nullable FIRST
- **CRITICAL**: `hashed` cast converts `null` to hash of empty string — must OMIT password from `User::create()` attributes, not pass `null` explicitly
- **InvalidSignatureException**: No handler exists in `bootstrap/app.php` — expired signed URLs would show raw 403 without custom handler
- **Double-hashing**: `UserManagementService::store()` calls `Hash::make()` but model's `hashed` cast already handles this — redundant but harmless

---

## Task 2 Issue: Notification Dependency

**Problem**: Task 3 (running in parallel) added `InvitationNotification` to `StoreUserController`, but the notification references route `invitation.accept` which doesn't exist until Task 4.

**Error**: 
```
Symfony\Component\Routing\Exception\RouteNotFoundException: Route [invitation.accept] not defined.
```

**Impact**: Tests fail because the notification tries to generate a URL for a non-existent route.

**Resolution Options**:
1. Temporarily comment out notification call until Task 4 completes
2. Wait for Task 4 to create the route before testing
3. Mock the notification in tests

**Chosen**: Option 1 - Comment out notification temporarily with TODO comment referencing Task 4.

**Note**: This is expected behavior when tasks run in parallel with dependencies. Task 4 will complete the integration.

