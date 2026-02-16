# User Invitation Flow

## TL;DR

> **Quick Summary**: Replace the password field in the admin "create user" form with an email invitation flow. Admin creates a user (name, email, role — no password), an invitation email is sent with a signed URL, and the user clicks the link to set their password. On submission, the user is logged in and their email is verified.
>
> **Deliverables**:
>
> - Modified admin create-user form (no password fields)
> - Database migration making `users.password` nullable
> - `InvitationNotification` class sending a signed URL email
> - Invitation acceptance page (password + confirmation form)
> - Resend invitation capability for pending users
> - Status indicator on users table (Invited vs Active)
> - Full Pest test coverage for the invitation lifecycle
>
> **Estimated Effort**: Medium
> **Parallel Execution**: YES — 4 waves
> **Critical Path**: Task 1 (migration) → Task 2 (backend store) → Task 3 (notification) → Task 4 (acceptance) → Task 6 (frontend form) → Task 8 (frontend table)

---

## Context

### Original Request

Admin can create a new user. Currently the admin enters a password — this needs to be removed. Instead, an invitation email is sent to the new user's email address. The invitation contains a link to set up their account. Clicking the link goes to a page where password and confirmation can be entered. Once submitted, the user is logged in and their email is verified.

### Interview Summary

**Key Discussions**:

- Invitation link expiration: 48 hours
- Resend invitation: YES — add a resend button for users who haven't accepted yet
- Test strategy: Tests after implementation using Pest v4

**Research Findings**:

- Current flow: `create-user-form.tsx` → `StoreUserController` → `StoreUserRequest` (validates password) → `UserManagementService→store()` (hashes password) → User created
- No custom Notifications/Mailables exist yet. Empty `app/Features/UserManagement/Notifications/` directory is ready
- Fortify configured with password reset + email verification. User model does NOT implement `MustVerifyEmail`
- `users.email_verified_at` column exists (nullable). `password_reset_tokens` table exists
- Existing Pest tests in `tests/Feature/UserManagement/UserCrudTest.php`
- Architecture: Feature-based with UserManagement, Auth, Settings features

### Metis Review

**Identified Gaps** (addressed):

- **CRITICAL — `users.password` is NOT NULL**: PostgreSQL will reject null passwords. Migration to make it nullable is a hard blocker — added as Task 1
- **CRITICAL — `hashed` cast converts null to hash of empty string**: Must omit `password` from `User::create()` attributes entirely (not pass null) so the column defaults to NULL
- **InvalidSignatureException handling**: No handler exists in `bootstrap/app.php`. Expired signed URLs would show a raw 403. Added handler to render proper Inertia page
- **Double-hashing redundancy**: `UserManagementService::store()` calls `Hash::make()` but the model's `hashed` cast already handles this. Removing the redundant call
- **Re-acceptance guard**: Must check `$user->password !== null` on acceptance route to prevent re-accepting an already-accepted invitation
- **Factory state needed**: `UserFactory` always generates passwords. Need `invited()` state for testing
- **Derive invitation status from data**: Use `password === null` to determine pending status. Add computed `has_password` to DTO for frontend. No new DB columns

---

## Work Objectives

### Core Objective

Replace password-based user creation with an invitation email flow so new users set their own passwords via a secure, time-limited link.

### Concrete Deliverables

- Migration: `users.password` made nullable
- Modified: `StoreUserRequest` — no password validation
- Modified: `UserManagementService::store()` — no password parameter, sends notification
- New: `InvitationNotification` with signed URL email
- New: `AcceptInvitationController` (GET: show form, POST: set password + login)
- New: `AcceptInvitationRequest` for password validation
- New: `ResendInvitationController` to re-send email
- Modified: `create-user-form.tsx` — password fields removed
- New: `resources/js/pages/auth/accept-invitation.tsx` — password setup page
- Modified: `users-table.tsx` — status indicator + resend button
- Modified: `UserManagementData` — `has_password` computed property
- New: `tests/Feature/UserManagement/UserInvitationTest.php`
- Modified: `tests/Feature/UserManagement/UserCrudTest.php` — updated for no-password flow

### Definition of Done

- [ ] Admin can create a user without entering a password
- [ ] Invitation email is sent to the new user's email
- [ ] Invitation link is valid for 48 hours
- [ ] Clicking valid link renders password setup page
- [ ] Clicking expired link shows a proper error page
- [ ] Setting password logs the user in and verifies their email
- [ ] Admin can resend invitation for pending users
- [ ] Users table shows invitation status
- [ ] All existing CRUD tests pass (no regressions)
- [ ] New invitation lifecycle tests pass

### Must Have

- Signed URL with 48-hour expiration for invitation links
- Password + confirmation form on acceptance page
- Auto-login and email verification on acceptance
- Resend capability for pending (unaccepted) users
- Status indicator on users table (Invited vs Active)
- Proper error page for expired/invalid links

### Must NOT Have (Guardrails)

- Do NOT create an `invitations` table — signed URLs are stateless, derive state from `password IS NULL`
- Do NOT add `status`, `invited_at`, or any new columns to users table
- Do NOT modify Fortify auth actions (`CreateNewUser`, `ResetUserPassword`) — separate flow
- Do NOT modify the edit-user form — it already has no password field
- Do NOT touch `config/fortify.php` or disable self-registration
- Do NOT build custom email templates — use standard `MailMessage` with `->line()` / `->action()`
- Do NOT queue the notification (keep synchronous for now)
- Do NOT add bulk invite capability
- Do NOT create a custom password strength indicator
- Do NOT add invitation history/audit log

---

## Verification Strategy (MANDATORY)

> **UNIVERSAL RULE: ZERO HUMAN INTERVENTION**
>
> ALL tasks in this plan MUST be verifiable WITHOUT any human action.
> ALL verification is executed by the agent using tools (Pest tests, curl, Playwright). No exceptions.

### Test Decision

- **Infrastructure exists**: YES
- **Automated tests**: Tests-after
- **Framework**: Pest v4

### Agent-Executed QA Scenarios (MANDATORY — ALL tasks)

> Every task includes Agent-Executed QA Scenarios as the primary verification method,
> complemented by Pest feature tests. The executing agent directly verifies deliverables
> by running commands, sending requests, and asserting outcomes.

**Verification Tool by Deliverable Type:**

| Type                    | Tool                            | How Agent Verifies                            |
| ----------------------- | ------------------------------- | --------------------------------------------- |
| **Migration**           | Bash (sail artisan)             | Run migration, check DB schema                |
| **Backend controllers** | Pest tests + Bash (curl)        | Feature tests + manual HTTP assertions        |
| **Notification**        | Pest tests (Notification::fake) | Assert notification sent with correct content |
| **Frontend pages**      | Playwright                      | Navigate, interact, assert DOM, screenshot    |
| **Integration**         | Pest tests                      | Full lifecycle tests                          |

---

## Execution Strategy

### Parallel Execution Waves

```
Wave 1 (Start Immediately):
└── Task 1: Migration (password nullable)

Wave 2 (After Wave 1):
├── Task 2: Backend — modify user creation flow
└── Task 3: Backend — InvitationNotification class

Wave 3 (After Wave 2):
├── Task 4: Backend — acceptance flow (controller + request + routes + exception handler)
└── Task 5: Backend — resend invitation controller

Wave 4 (After Wave 3):
├── Task 6: Frontend — modify create-user form
├── Task 7: Frontend — acceptance page
└── Task 8: Frontend — users table status + resend button

Wave 5 (After Wave 4):
└── Task 9: Tests + Wayfinder regeneration + final verification
```

### Dependency Matrix

| Task | Depends On | Blocks     | Can Parallelize With   |
| ---- | ---------- | ---------- | ---------------------- |
| 1    | None       | 2, 3       | None (quick, do first) |
| 2    | 1          | 4, 5, 6, 8 | 3                      |
| 3    | 1          | 4, 5       | 2                      |
| 4    | 2, 3       | 7, 9       | 5                      |
| 5    | 2, 3       | 8, 9       | 4                      |
| 6    | 2          | 9          | 7, 8                   |
| 7    | 4          | 9          | 6, 8                   |
| 8    | 2, 5       | 9          | 6, 7                   |
| 9    | All        | None       | None (final)           |

### Agent Dispatch Summary

| Wave | Tasks   | Recommended Agents                                                                             |
| ---- | ------- | ---------------------------------------------------------------------------------------------- |
| 1    | 1       | `task(category="quick", load_skills=["pest-testing"])`                                         |
| 2    | 2, 3    | `task(category="unspecified-low", ...)` parallel                                               |
| 3    | 4, 5    | `task(category="unspecified-low", ...)` parallel                                               |
| 4    | 6, 7, 8 | `task(category="visual-engineering", load_skills=["inertia-react-development", ...])` parallel |
| 5    | 9       | `task(category="unspecified-high", load_skills=["pest-testing"])`                              |

---

## TODOs

- [ ]   1. Migration: Make `users.password` nullable

    **What to do**:
    - Create migration via `vendor/bin/sail artisan make:migration make_user_password_nullable --no-interaction`
    - In `up()`: `$table->string('password')->nullable()->change();`
    - In `down()`: `$table->string('password')->nullable(false)->change();`
    - Run migration: `vendor/bin/sail artisan migrate --no-interaction`
    - Verify with `vendor/bin/sail artisan tinker` or DB schema check that password is now nullable

    **Must NOT do**:
    - Do NOT add any new columns (no `status`, no `invited_at`)
    - Do NOT modify any other columns
    - Do NOT touch user model or factory yet (that's Task 2)

    **Recommended Agent Profile**:
    - **Category**: `quick`
    - **Skills**: [`pest-testing`]
        - `pest-testing`: Needed for running existing tests to verify migration doesn't break anything
    - **Skills Evaluated but Omitted**:
        - `tailwindcss-development`: No styling involved
        - `inertia-react-development`: No frontend work

    **Parallelization**:
    - **Can Run In Parallel**: NO
    - **Parallel Group**: Wave 1 (solo)
    - **Blocks**: Tasks 2, 3
    - **Blocked By**: None (start immediately)

    **References**:

    **Pattern References**:
    - `database/migrations/0001_01_01_000000_create_users_table.php` — Original users table schema showing current `password` column definition as `$table->string('password')`

    **API/Type References**:
    - `app/Models/User.php:46-49` — The `casts()` method showing `'password' => 'hashed'` cast — this cast is relevant because it affects how null passwords are handled (see Must NOT do)

    **Documentation References**:
    - Laravel migration column modifiers: `->nullable()->change()` syntax for altering existing columns

    **WHY Each Reference Matters**:
    - The original migration shows the exact column definition being altered
    - The model cast is critical context: the `hashed` cast converts `null` to a hash of empty string, so later tasks must omit `password` from create attributes entirely rather than passing null

    **Acceptance Criteria**:
    - [ ] Migration file created in `database/migrations/`
    - [ ] `vendor/bin/sail artisan migrate --no-interaction` exits 0
    - [ ] DB schema confirms `users.password` is nullable: `vendor/bin/sail artisan tinker --execute="echo Schema::hasColumn('users', 'password') ? 'exists' : 'missing';"` (or use `database-schema` tool to inspect)
    - [ ] Existing tests still pass: `vendor/bin/sail artisan test --compact --filter=UserCrudTest`

    **Agent-Executed QA Scenarios:**

    ```
    Scenario: Migration runs successfully
      Tool: Bash (sail artisan)
      Preconditions: Database is up and current migrations applied
      Steps:
        1. Run: vendor/bin/sail artisan migrate --no-interaction
        2. Assert: exit code 0
        3. Run: vendor/bin/sail artisan db:show (or database-schema tool)
        4. Assert: users.password column is nullable
      Expected Result: Migration applies cleanly, password column is now nullable
      Evidence: Command output captured

    Scenario: Migration rollback works
      Tool: Bash (sail artisan)
      Preconditions: Migration has been applied
      Steps:
        1. Run: vendor/bin/sail artisan migrate:rollback --step=1 --no-interaction
        2. Assert: exit code 0
        3. Run: database-schema tool
        4. Assert: users.password column is NOT nullable
        5. Run: vendor/bin/sail artisan migrate --no-interaction (re-apply)
      Expected Result: Rollback restores NOT NULL constraint, re-migrate works
      Evidence: Command output captured

    Scenario: Existing user CRUD tests still pass
      Tool: Bash (sail artisan)
      Preconditions: Migration applied
      Steps:
        1. Run: vendor/bin/sail artisan test --compact --filter=UserCrudTest
        2. Assert: all tests pass, 0 failures
      Expected Result: No regressions from making password nullable
      Evidence: Test output captured
    ```

    **Commit**: YES
    - Message: `feat(users): make password column nullable for invitation flow`
    - Files: `database/migrations/*_make_user_password_nullable.php`
    - Pre-commit: `vendor/bin/sail artisan test --compact --filter=UserCrudTest`

---

- [ ]   2. Backend: Modify user creation flow + factory state

    **What to do**:
    - **StoreUserRequest** (`app/Features/UserManagement/Requests/StoreUserRequest.php`):
        - Remove `password` and `password_confirmation` validation rules entirely
        - Only validate: `name` (required, string, max:255), `email` (required, email, unique), `role` (required, enum)
    - **UserManagementService** (`app/Features/UserManagement/Services/UserManagementService.php`):
        - Modify `store()` method signature: remove `string $password` parameter
        - In `User::create()`, only pass `name` and `email` — do NOT pass `password` at all (critical: omitting it means the nullable column defaults to NULL; passing `null` explicitly would be converted to a hash of empty string by the `hashed` cast)
        - Remove the `Hash::make($password)` call and the `use Illuminate\Support\Facades\Hash;` import
    - **StoreUserController** (`app/Features/UserManagement/Controllers/StoreUserController.php`):
        - Stop extracting `password` from validated request data
        - Call `$this->userManagementService->store()` without password argument
    - **UserFactory** (`database/factories/UserFactory.php`):
        - Add `invited()` state: sets `password => null` and `email_verified_at => null`
        - Note: must use `->state(['password' => null])` — the factory state bypasses the cast when setting raw attributes via `$this->state()`... Actually, the `hashed` cast will still run. Use `afterCreating` or DB update to set null. Alternatively, create user then update password to null via query builder: `->afterCreating(fn (User $user) => DB::table('users')->where('id', $user->id)->update(['password' => null]))`. OR simply use `->state(fn () => ['password' => null])` and verify if the cast handles it — if not, use `afterCreating` with raw DB update.

    **Must NOT do**:
    - Do NOT modify the edit user flow (`UpdateUserRequest`, `update()` in service)
    - Do NOT send the notification yet (that's Task 3)
    - Do NOT modify the User model itself
    - Do NOT add any new columns or tables

    **Recommended Agent Profile**:
    - **Category**: `unspecified-low`
    - **Skills**: [`pest-testing`]
        - `pest-testing`: Needed for running and updating existing CRUD tests after modifying the store flow
    - **Skills Evaluated but Omitted**:
        - `inertia-react-development`: No frontend work in this task
        - `developing-with-fortify`: Not touching Fortify actions

    **Parallelization**:
    - **Can Run In Parallel**: YES
    - **Parallel Group**: Wave 2 (with Task 3)
    - **Blocks**: Tasks 4, 5, 6, 8
    - **Blocked By**: Task 1

    **References**:

    **Pattern References**:
    - `app/Features/UserManagement/Requests/StoreUserRequest.php:19-24` — Current validation rules including password. Remove password and password_confirmation rules, keep name/email/role
    - `app/Features/UserManagement/Services/UserManagementService.php:22-33` — Current `store()` method that accepts password and calls `Hash::make()`. Remove password parameter, omit password from `User::create()` attributes
    - `app/Features/UserManagement/Controllers/StoreUserController.php:21-33` — Current controller extracting password from request. Stop extracting password, adjust service call

    **API/Type References**:
    - `app/Models/User.php:46-49` — `casts()` method with `'password' => 'hashed'`. CRITICAL: this cast converts null to hash of empty string. Must OMIT password from create attributes, not pass null
    - `app/Features/UserManagement/Enums/Role.php` — Role enum used in validation rule

    **Test References**:
    - `tests/Feature/UserManagement/UserCrudTest.php:57-92` — Existing test `admins can create a user` that posts with password. Must be updated to post WITHOUT password fields and assert `password IS NULL` in DB
    - `tests/Feature/UserManagement/UserCrudTest.php:75-79` — Validation test `store validates required fields` that asserts password error. Must be updated to NOT expect password validation error

    **Documentation References**:
    - `database/factories/UserFactory.php:27-40` — Current factory definition. Add `invited()` state for test users without passwords

    **WHY Each Reference Matters**:
    - StoreUserRequest shows exact rules to modify (remove 2 lines, keep 3)
    - Service method shows the signature change needed and the Hash::make removal
    - Controller shows where password is extracted from request (line to remove)
    - Model cast explains WHY we omit password vs passing null (the most subtle part of this task)
    - Existing tests show exactly what assertions need updating

    **Acceptance Criteria**:
    - [ ] `StoreUserRequest` no longer validates `password` or `password_confirmation`
    - [ ] `UserManagementService::store()` no longer accepts a password parameter
    - [ ] `User::create()` call does not include `password` in attributes array
    - [ ] `Hash::make()` call and `Hash` import removed from service
    - [ ] `UserFactory` has `invited()` state producing users with `password = NULL` and `email_verified_at = NULL`
    - [ ] `vendor/bin/sail bin pint --dirty --format agent` passes
    - [ ] Updated CRUD tests pass: `vendor/bin/sail artisan test --compact --filter=UserCrudTest`

    **Agent-Executed QA Scenarios:**

    ```
    Scenario: Admin creates user without password
      Tool: Bash (Pest test)
      Preconditions: Migration from Task 1 applied
      Steps:
        1. Run: vendor/bin/sail artisan test --compact --filter="admins can create a user"
        2. Assert: test passes
        3. Verify in test: POST /users with {name, email, role} (no password) → redirect
        4. Verify in test: assertDatabaseHas users with email AND password IS NULL
      Expected Result: User created with null password
      Evidence: Test output captured

    Scenario: Validation no longer requires password
      Tool: Bash (Pest test)
      Preconditions: Migration applied
      Steps:
        1. Run: vendor/bin/sail artisan test --compact --filter="store validates required fields"
        2. Assert: test passes
        3. Verify in test: POST /users with {} → errors for name, email, role only (NOT password)
      Expected Result: Password is not a required field
      Evidence: Test output captured

    Scenario: Factory invited() state creates passwordless user
      Tool: Bash (tinker)
      Preconditions: Migration applied, factory updated
      Steps:
        1. Run tinker: User::factory()->invited()->create()
        2. Assert: user has password = null
        3. Assert: user has email_verified_at = null
      Expected Result: Factory state produces correct invited user
      Evidence: Tinker output captured

    Scenario: Pint formatting passes
      Tool: Bash
      Preconditions: PHP files modified
      Steps:
        1. Run: vendor/bin/sail bin pint --dirty --format agent
        2. Assert: exit code 0 or only formatting fixes applied
      Expected Result: Code style compliant
      Evidence: Command output captured
    ```

    **Commit**: YES
    - Message: `refactor(users): remove password from admin user creation flow`
    - Files: `app/Features/UserManagement/Requests/StoreUserRequest.php`, `app/Features/UserManagement/Services/UserManagementService.php`, `app/Features/UserManagement/Controllers/StoreUserController.php`, `database/factories/UserFactory.php`, `tests/Feature/UserManagement/UserCrudTest.php`
    - Pre-commit: `vendor/bin/sail artisan test --compact --filter=UserCrudTest`

---

- [ ]   3. Backend: InvitationNotification class

    **What to do**:
    - Create notification: `vendor/bin/sail artisan make:notification InvitationNotification --no-interaction` — then move to `app/Features/UserManagement/Notifications/` (artisan creates in `app/Notifications/` by default)
    - Implement `toMail()` method:
        - Use `MailMessage` with `->subject('You\'ve been invited')`, `->line('You have been invited to join...')`, `->action('Set Up Your Account', $url)`, `->line('This link expires in 48 hours.')`
        - Generate signed URL in `toMail()`: `URL::temporarySignedRoute('invitation.accept', now()->addHours(48), ['user' => $this->user->id])`
        - Pass the `User` model via constructor (store as public property)
    - Define the `via()` method returning `['mail']`
    - Dispatch notification in `StoreUserController` after user creation: `$user->notify(new InvitationNotification($user))`
    - Import the notification in the controller

    **Must NOT do**:
    - Do NOT queue the notification (no `ShouldQueue`)
    - Do NOT create a custom Blade email template
    - Do NOT create a separate Mailable class — use the Notification's built-in `MailMessage`
    - Do NOT dispatch from the service (service must not have side effects beyond DB persistence)

    **Recommended Agent Profile**:
    - **Category**: `unspecified-low`
    - **Skills**: [`pest-testing`]
        - `pest-testing`: Needed for writing notification assertion tests
    - **Skills Evaluated but Omitted**:
        - `inertia-react-development`: No frontend work
        - `developing-with-fortify`: Not using Fortify notification patterns

    **Parallelization**:
    - **Can Run In Parallel**: YES
    - **Parallel Group**: Wave 2 (with Task 2)
    - **Blocks**: Tasks 4, 5
    - **Blocked By**: Task 1

    **References**:

    **Pattern References**:
    - `app/Features/UserManagement/Controllers/StoreUserController.php` — Where notification dispatch will be added (after `$user = $this->userManagementService->store(...)`)
    - `app/Models/User.php:25` — User model uses `Notifiable` trait, so `$user->notify()` is available

    **API/Type References**:
    - `app/Features/UserManagement/Notifications/` — Empty directory where the notification class must be placed
    - Route name `invitation.accept` — will be created in Task 4, but notification references it for signed URL generation

    **Documentation References**:
    - Laravel Notifications documentation: `MailMessage` API with `->subject()`, `->line()`, `->action()`, `->salutation()`
    - Laravel signed URLs: `URL::temporarySignedRoute($name, $expiration, $parameters)`

    **WHY Each Reference Matters**:
    - StoreUserController shows exactly where to add the dispatch (after user creation, before redirect)
    - User model confirms Notifiable trait is present
    - Empty Notifications directory confirms this is the correct feature-scoped location
    - Route name is needed for the signed URL — notification must reference the route that Task 4 creates

    **Acceptance Criteria**:
    - [ ] `InvitationNotification` class exists at `app/Features/UserManagement/Notifications/InvitationNotification.php`
    - [ ] Class accepts `User` in constructor, returns `['mail']` from `via()`, returns `MailMessage` from `toMail()`
    - [ ] `MailMessage` includes subject, body text, action button with signed URL, and expiry notice
    - [ ] Signed URL uses `URL::temporarySignedRoute('invitation.accept', now()->addHours(48), ['user' => $user->id])`
    - [ ] `StoreUserController` dispatches notification after creating user
    - [ ] `vendor/bin/sail bin pint --dirty --format agent` passes
    - [ ] Notification dispatch can be asserted: `Notification::fake()` + `Notification::assertSentTo($user, InvitationNotification::class)`

    **Agent-Executed QA Scenarios:**

    ```
    Scenario: Notification sent when admin creates user
      Tool: Bash (Pest test)
      Preconditions: Tasks 1-2 complete, notification class created
      Steps:
        1. Write test: Notification::fake(), actingAs admin, POST /users with {name, email, role}
        2. Assert: Notification::assertSentTo(created user, InvitationNotification::class)
        3. Run: vendor/bin/sail artisan test --compact --filter="invitation notification is sent"
        4. Assert: test passes
      Expected Result: Notification dispatched to newly created user
      Evidence: Test output captured

    Scenario: Notification email contains correct content
      Tool: Bash (Pest test)
      Preconditions: Notification class created
      Steps:
        1. Write test: Create InvitationNotification, call toMail(), inspect MailMessage
        2. Assert: subject contains "invited"
        3. Assert: actionUrl contains route('invitation.accept') with signature
        4. Assert: actionUrl contains user ID parameter
        5. Run test
      Expected Result: Email content is correct with valid signed URL
      Evidence: Test output captured

    Scenario: Pint formatting passes
      Tool: Bash
      Steps:
        1. Run: vendor/bin/sail bin pint --dirty --format agent
        2. Assert: exit code 0
      Expected Result: Code style compliant
      Evidence: Command output captured
    ```

    **Commit**: YES
    - Message: `feat(users): add invitation notification with signed URL`
    - Files: `app/Features/UserManagement/Notifications/InvitationNotification.php`, `app/Features/UserManagement/Controllers/StoreUserController.php`
    - Pre-commit: `vendor/bin/sail artisan test --compact --filter=UserCrudTest`

---

- [ ]   4. Backend: Invitation acceptance flow

    **What to do**:
    - **AcceptInvitationRequest** — create via `vendor/bin/sail artisan make:request AcceptInvitationRequest --no-interaction`, move to `app/Features/UserManagement/Requests/`:
        - Validate: `password` (required, string, Password::defaults(), confirmed), `password_confirmation`
        - Authorization: return `true` (URL signature handles auth)
    - **AcceptInvitationController** — create via `vendor/bin/sail artisan make:controller AcceptInvitationController --no-interaction`, move to `app/Features/UserManagement/Controllers/`:
        - Invokable is NOT suitable here (needs both show + store). Use two methods: `show()` and `store()`
        - **Actually**: Create TWO invokable controllers to match existing pattern:
            - `ShowAcceptInvitationController` — GET, renders Inertia page
            - `StoreAcceptInvitationController` — POST, sets password + logs in
        - **`ShowAcceptInvitationController::__invoke(Request $request, User $user)`**:
            - Check if user already accepted: if `$user->password !== null`, redirect to login with flash "Invitation already accepted"
            - Render Inertia page: `Inertia::render('auth/accept-invitation', ['user' => $user])`
        - **`StoreAcceptInvitationController::__invoke(AcceptInvitationRequest $request, User $user)`**:
            - Check if user already accepted: if `$user->password !== null`, redirect to login
            - Set password: `$user->update(['password' => $request->validated('password')])` (the `hashed` cast handles hashing)
            - Set email verified: `$user->update(['email_verified_at' => now()])`
            - Login user: `Auth::login($user)`
            - Redirect to `/dashboard`
    - **Routes** — add to `routes/web.php`:
        - `Route::get('/invitation/accept/{user}', ShowAcceptInvitationController::class)->name('invitation.accept')->middleware(['signed', 'guest'])`
        - `Route::post('/invitation/accept/{user}', StoreAcceptInvitationController::class)->name('invitation.store')->middleware('guest')`
        - Note: `signed` middleware on GET only (validates the signed URL). POST does not need signature validation — the user is already on the page.
    - **InvalidSignatureException handler** — in `bootstrap/app.php`:
        - In the `withExceptions` closure, handle `Illuminate\Routing\Exceptions\InvalidSignatureException`:
        - If request expects Inertia: render an Inertia error page (e.g., `auth/invitation-expired`)
        - Otherwise: return a standard 403 response
        - Create a minimal Inertia page `resources/js/pages/auth/invitation-expired.tsx` that shows "This invitation link has expired" with a message to contact admin

    **Must NOT do**:
    - Do NOT use a single controller with multiple methods (follow the invokable pattern used everywhere else)
    - Do NOT validate the signed URL manually in the controller — use `signed` middleware
    - Do NOT create the acceptance Inertia page component yet (that's Task 7) — just define the route and controller
    - Do NOT modify Fortify actions or routes
    - Do NOT create a service for this — it's simple enough for the controller
    - Do NOT check password !== null in the POST by reading request signature — just check the user model

    **Recommended Agent Profile**:
    - **Category**: `unspecified-low`
    - **Skills**: [`pest-testing`, `developing-with-fortify`]
        - `pest-testing`: Needed for writing acceptance flow tests
        - `developing-with-fortify`: Useful for understanding auth patterns (Auth::login, Password::defaults)
    - **Skills Evaluated but Omitted**:
        - `inertia-react-development`: No frontend component work in this task

    **Parallelization**:
    - **Can Run In Parallel**: YES
    - **Parallel Group**: Wave 3 (with Task 5)
    - **Blocks**: Tasks 7, 9
    - **Blocked By**: Tasks 2, 3

    **References**:

    **Pattern References**:
    - `app/Features/UserManagement/Controllers/StoreUserController.php` — Invokable controller pattern with constructor-injected service and Gate authorization. Follow this structure for both new controllers
    - `app/Features/UserManagement/Controllers/CreateUserController.php` — GET controller pattern that renders Inertia pages. Follow for ShowAcceptInvitationController
    - `app/Features/UserManagement/Requests/StoreUserRequest.php` — FormRequest pattern. Follow for AcceptInvitationRequest

    **API/Type References**:
    - `app/Models/User.php:46-49` — `casts()` with `'password' => 'hashed'` — when setting password via `$user->update(['password' => $value])`, the cast auto-hashes. No manual Hash::make needed
    - `bootstrap/app.php` — Current exception handler (empty `withExceptions` closure) — add InvalidSignatureException handling here
    - `routes/web.php:37-43` — Existing user management routes. Add invitation routes nearby

    **Test References**:
    - `tests/Feature/UserManagement/UserCrudTest.php` — Test patterns: `actingAs(createAdmin())`, `$this->post(route(...))`, `assertRedirect`, `assertDatabaseHas`

    **Documentation References**:
    - Laravel signed URLs middleware: `->middleware('signed')` on routes
    - Laravel `Auth::login($user)` for manual authentication
    - Laravel `InvalidSignatureException` for handling expired/tampered signatures
    - Inertia rendering: `Inertia::render('page-name', ['prop' => $value])`

    **WHY Each Reference Matters**:
    - Existing controllers show the exact invokable pattern to replicate
    - The `hashed` cast means we DON'T call Hash::make — just pass raw password to update()
    - bootstrap/app.php is where exception rendering must be added
    - Route file shows where to place new routes and what middleware groups are used
    - Test patterns ensure consistent test structure

    **Acceptance Criteria**:
    - [ ] `ShowAcceptInvitationController` exists in `app/Features/UserManagement/Controllers/`
    - [ ] `StoreAcceptInvitationController` exists in `app/Features/UserManagement/Controllers/`
    - [ ] `AcceptInvitationRequest` exists in `app/Features/UserManagement/Requests/`
    - [ ] Routes registered: `invitation.accept` (GET, signed+guest) and `invitation.store` (POST, guest)
    - [ ] GET valid signed URL → 200, renders `auth/accept-invitation` Inertia page
    - [ ] GET expired/tampered URL → renders `auth/invitation-expired` Inertia page (not raw 403)
    - [ ] GET for already-accepted user → redirect to login
    - [ ] POST with valid password → password set, email_verified_at set, user logged in, redirect to /dashboard
    - [ ] POST for already-accepted user → redirect to login
    - [ ] `vendor/bin/sail bin pint --dirty --format agent` passes

    **Agent-Executed QA Scenarios:**

    ```
    Scenario: Valid signed URL renders acceptance page
      Tool: Bash (Pest test)
      Preconditions: Invited user exists (factory invited state), routes registered
      Steps:
        1. Create invited user via factory
        2. Generate signed URL: URL::temporarySignedRoute('invitation.accept', now()->addHours(48), ['user' => $user->id])
        3. GET the signed URL
        4. Assert: response status 200
        5. Assert: Inertia page component is 'auth/accept-invitation'
      Expected Result: Acceptance page renders for valid invitation
      Evidence: Test output captured

    Scenario: Expired signed URL shows expiry page
      Tool: Bash (Pest test)
      Preconditions: Invited user exists, routes registered
      Steps:
        1. Create invited user via factory
        2. Generate signed URL with past expiry: URL::temporarySignedRoute('invitation.accept', now()->subHour(), ['user' => $user->id])
        3. GET the expired URL
        4. Assert: Inertia page component is 'auth/invitation-expired' (or appropriate error rendering)
      Expected Result: User sees friendly expiry message, not raw 403
      Evidence: Test output captured

    Scenario: Accepting invitation sets password and logs in
      Tool: Bash (Pest test)
      Preconditions: Invited user exists, routes registered
      Steps:
        1. Create invited user (password null, email_verified_at null)
        2. POST to invitation.store with {password: 'NewPass123!', password_confirmation: 'NewPass123!'}
        3. Assert: $user->fresh()->password is not null
        4. Assert: $user->fresh()->email_verified_at is not null
        5. Assert: Auth::check() is true
        6. Assert: redirect to /dashboard
      Expected Result: User fully set up and logged in
      Evidence: Test output captured

    Scenario: Already-accepted user redirected on GET
      Tool: Bash (Pest test)
      Preconditions: User with password already set
      Steps:
        1. Create user with password (normal factory)
        2. Generate signed URL for this user
        3. GET the signed URL
        4. Assert: redirect to login route
      Expected Result: Cannot re-accept invitation
      Evidence: Test output captured

    Scenario: Password validation on acceptance
      Tool: Bash (Pest test)
      Preconditions: Invited user exists
      Steps:
        1. POST with {password: 'short'} (no confirmation)
        2. Assert: session has errors for password
        3. POST with {password: 'ValidPass123!', password_confirmation: 'Different123!'}
        4. Assert: session has errors for password (confirmation mismatch)
      Expected Result: Standard password validation applies
      Evidence: Test output captured
    ```

    **Commit**: YES
    - Message: `feat(users): add invitation acceptance controllers and routes`
    - Files: `app/Features/UserManagement/Controllers/ShowAcceptInvitationController.php`, `app/Features/UserManagement/Controllers/StoreAcceptInvitationController.php`, `app/Features/UserManagement/Requests/AcceptInvitationRequest.php`, `routes/web.php`, `bootstrap/app.php`, `resources/js/pages/auth/invitation-expired.tsx`
    - Pre-commit: `vendor/bin/sail artisan test --compact --filter=UserCrudTest`

---

- [ ]   5. Backend: Resend invitation controller

    **What to do**:
    - **ResendInvitationController** — create invokable controller in `app/Features/UserManagement/Controllers/`:
        - `__invoke(Request $request, User $user)`:
            - Authorize: `Gate::authorize('create', User::class)` (same permission as creating users)
            - Guard: if `$user->password !== null`, return back with error (cannot resend to accepted user) — or abort(422)
            - Re-send: `$user->notify(new InvitationNotification($user))` (generates fresh signed URL)
            - Return: redirect back with success flash
    - **Route** — add to `routes/web.php`:
        - `Route::post('/users/{user}/resend-invitation', ResendInvitationController::class)->name('users.resend-invitation')->middleware(['auth', 'verified'])`
        - Place alongside other user management routes

    **Must NOT do**:
    - Do NOT create a separate form request (simple enough for inline validation)
    - Do NOT create a service method for this (single responsibility: just re-send notification)
    - Do NOT allow resending to users who already have passwords
    - Do NOT add rate limiting (keep simple for now)

    **Recommended Agent Profile**:
    - **Category**: `quick`
    - **Skills**: [`pest-testing`]
        - `pest-testing`: Needed for writing resend tests
    - **Skills Evaluated but Omitted**:
        - `inertia-react-development`: No frontend work

    **Parallelization**:
    - **Can Run In Parallel**: YES
    - **Parallel Group**: Wave 3 (with Task 4)
    - **Blocks**: Tasks 8, 9
    - **Blocked By**: Tasks 2, 3

    **References**:

    **Pattern References**:
    - `app/Features/UserManagement/Controllers/StoreUserController.php` — Invokable controller with Gate authorization. Follow exact same pattern
    - `app/Features/UserManagement/Notifications/InvitationNotification.php` — (created in Task 3) The notification class to re-dispatch

    **API/Type References**:
    - `app/Features/UserManagement/Enums/Permission.php` — Permission enum for Gate authorization
    - `app/Features/UserManagement/Policies/UserPolicy.php` — Policy for user authorization

    **Test References**:
    - `tests/Feature/UserManagement/UserCrudTest.php` — Authorization test pattern (testing both admin and non-admin access)

    **WHY Each Reference Matters**:
    - StoreUserController shows the exact Gate pattern for user management authorization
    - InvitationNotification is what gets re-dispatched
    - Permission enum may have relevant permissions to check
    - Existing tests show how to test admin-only routes

    **Acceptance Criteria**:
    - [ ] `ResendInvitationController` exists in `app/Features/UserManagement/Controllers/`
    - [ ] Route `users.resend-invitation` registered (POST, auth+verified middleware)
    - [ ] Admin can resend invitation for user with null password → notification sent
    - [ ] Admin cannot resend invitation for user with password → 422 or error
    - [ ] Non-admin cannot resend invitation → 403
    - [ ] `vendor/bin/sail bin pint --dirty --format agent` passes

    **Agent-Executed QA Scenarios:**

    ```
    Scenario: Admin resends invitation to pending user
      Tool: Bash (Pest test)
      Preconditions: Invited user exists (password null)
      Steps:
        1. Notification::fake()
        2. actingAs admin, POST /users/{user}/resend-invitation
        3. Assert: redirect back (302)
        4. Assert: Notification::assertSentTo($user, InvitationNotification::class)
      Expected Result: Fresh invitation email sent
      Evidence: Test output captured

    Scenario: Resend blocked for accepted user
      Tool: Bash (Pest test)
      Preconditions: User with password set (already accepted)
      Steps:
        1. Notification::fake()
        2. actingAs admin, POST /users/{user}/resend-invitation
        3. Assert: status 422 or session has error
        4. Assert: Notification::assertNothingSent()
      Expected Result: Cannot resend to already-accepted user
      Evidence: Test output captured

    Scenario: Non-admin cannot resend
      Tool: Bash (Pest test)
      Preconditions: Invited user exists, regular user acting
      Steps:
        1. actingAs regular user, POST /users/{user}/resend-invitation
        2. Assert: status 403
      Expected Result: Authorization enforced
      Evidence: Test output captured
    ```

    **Commit**: YES (groups with Task 4)
    - Message: `feat(users): add resend invitation controller`
    - Files: `app/Features/UserManagement/Controllers/ResendInvitationController.php`, `routes/web.php`
    - Pre-commit: `vendor/bin/sail artisan test --compact --filter=UserCrudTest`

---

- [ ]   6. Frontend: Modify create-user form

    **What to do**:
    - **`resources/js/components/user-management/create-user-form.tsx`**:
        - Remove `password` and `password_confirmation` from the `useForm()` initial data object
        - Remove the password `<Input>` field and its `<Label>` + error display
        - Remove the password_confirmation `<Input>` field and its `<Label>` + error display
        - Keep: name, email, role fields exactly as they are
    - **`resources/js/pages/user-management/create.tsx`** — NO changes needed (thin page wrapper)
    - Run `vendor/bin/sail npm run build` to verify no build errors

    **Must NOT do**:
    - Do NOT modify the edit-user form
    - Do NOT change any field styling or layout beyond removing password fields
    - Do NOT add any new fields (like "send invitation" checkbox — it always sends)
    - Do NOT modify the page component — only the form component

    **Recommended Agent Profile**:
    - **Category**: `quick`
    - **Skills**: [`inertia-react-development`, `tailwindcss-development`]
        - `inertia-react-development`: Working with Inertia useForm and React form patterns
        - `tailwindcss-development`: May need minor layout adjustment after removing fields
    - **Skills Evaluated but Omitted**:
        - `pest-testing`: No PHP tests in this task
        - `developing-with-fortify`: Not auth-related frontend

    **Parallelization**:
    - **Can Run In Parallel**: YES
    - **Parallel Group**: Wave 4 (with Tasks 7, 8)
    - **Blocks**: Task 9
    - **Blocked By**: Task 2

    **References**:

    **Pattern References**:
    - `resources/js/components/user-management/create-user-form.tsx:17-122` — The FULL current form component. Remove the password-related JSX blocks (Input fields, Labels, InputError components for password and password_confirmation). Keep name, email, role fields intact

    **API/Type References**:
    - Wayfinder route: The form's `post()` call uses a Wayfinder-generated store route function. This does NOT change — same route, fewer fields

    **WHY Each Reference Matters**:
    - The form component is the only file to modify — need to see full structure to know which JSX blocks to remove
    - Wayfinder route confirms no URL changes needed

    **Acceptance Criteria**:
    - [ ] `create-user-form.tsx` has no password or password_confirmation fields
    - [ ] `useForm()` initial data does not include password or password_confirmation
    - [ ] Name, email, and role fields remain unchanged
    - [ ] `vendor/bin/sail npm run build` exits 0
    - [ ] No TypeScript errors

    **Agent-Executed QA Scenarios:**

    ```
    Scenario: Create user form renders without password fields
      Tool: Playwright (playwright skill)
      Preconditions: Dev server running (vendor/bin/sail npm run dev), admin logged in
      Steps:
        1. Navigate to: /users/create
        2. Wait for: form visible (timeout: 5s)
        3. Assert: input[name="name"] exists
        4. Assert: input[name="email"] exists
        5. Assert: select or input for role exists
        6. Assert: input[name="password"] does NOT exist
        7. Assert: input[name="password_confirmation"] does NOT exist
        8. Screenshot: .sisyphus/evidence/task-6-create-form-no-password.png
      Expected Result: Form shows name, email, role only
      Evidence: .sisyphus/evidence/task-6-create-form-no-password.png

    Scenario: Form submits successfully without password
      Tool: Playwright (playwright skill)
      Preconditions: Dev server running, admin logged in
      Steps:
        1. Navigate to: /users/create
        2. Fill: input[name="name"] → "Test Invited User"
        3. Fill: input[name="email"] → "invited@test.com"
        4. Select role (first non-admin option)
        5. Click: submit button
        6. Wait for: navigation to /users (timeout: 10s)
        7. Assert: URL is /users
        8. Screenshot: .sisyphus/evidence/task-6-form-submit-success.png
      Expected Result: User created and redirected to users list
      Evidence: .sisyphus/evidence/task-6-form-submit-success.png
    ```

    **Commit**: YES
    - Message: `feat(users): remove password fields from create user form`
    - Files: `resources/js/components/user-management/create-user-form.tsx`
    - Pre-commit: `vendor/bin/sail npm run build`

---

- [ ]   7. Frontend: Invitation acceptance page

    **What to do**:
    - **Create `resources/js/pages/auth/accept-invitation.tsx`**:
        - Follow the auth page pattern (thin page → `AuthLayout` → form component)
        - Receive props: `{ user: { name: string } }` (from ShowAcceptInvitationController)
        - Use `AuthLayout` with a welcome heading: "Welcome, {user.name}" or "Set Up Your Account"
        - Render a form component (can be inline for simplicity since it's only used here)
        - Form fields: `password` (Input, type="password"), `password_confirmation` (Input, type="password")
        - Use Inertia `useForm` with POST to `invitation.store` route (via Wayfinder: import the store action)
        - Submit button: "Set Password" or "Complete Setup"
        - After successful submission, Inertia handles redirect to /dashboard automatically
    - **Create `resources/js/pages/auth/invitation-expired.tsx`** (minimal error page):
        - Use `AuthLayout`
        - Show message: "This invitation link has expired or is invalid"
        - Suggest contacting admin for a new invitation
        - No form, no actions — just information
    - Run `vendor/bin/sail npm run build`
    - Run `vendor/bin/sail artisan wayfinder:generate` to pick up new routes (if not already done)

    **Must NOT do**:
    - Do NOT build a custom password strength indicator
    - Do NOT add "terms and conditions" checkbox
    - Do NOT add name/email fields (already set by admin)
    - Do NOT build a complex multi-step wizard — single form, two fields

    **Recommended Agent Profile**:
    - **Category**: `visual-engineering`
    - **Skills**: [`inertia-react-development`, `tailwindcss-development`, `wayfinder-development`]
        - `inertia-react-development`: Creating Inertia page with useForm, handling form submission
        - `tailwindcss-development`: Styling the auth page to match existing patterns
        - `wayfinder-development`: Importing the store route for form submission
    - **Skills Evaluated but Omitted**:
        - `pest-testing`: No PHP tests in this task
        - `developing-with-fortify`: Not modifying Fortify, just building a similar-looking auth page

    **Parallelization**:
    - **Can Run In Parallel**: YES
    - **Parallel Group**: Wave 4 (with Tasks 6, 8)
    - **Blocks**: Task 9
    - **Blocked By**: Task 4

    **References**:

    **Pattern References**:
    - `resources/js/pages/auth/verify-email.tsx` — Auth page pattern: thin page using AuthLayout, typed PageProps. Follow this exact structure for `accept-invitation.tsx`
    - `resources/js/components/auth/register-form.tsx` — Form with password fields pattern: useForm + password Input + submit. Follow for the password form within the acceptance page
    - `resources/js/layouts/auth-layout.tsx` — The layout component to wrap the acceptance page. Shows expected props (title, description, children)

    **API/Type References**:
    - Wayfinder route for `invitation.store` — import from `@/actions/...` (generated after `wayfinder:generate`). Use with `useForm` post submission
    - PageProps typing pattern from existing auth pages

    **Documentation References**:
    - Inertia.js useForm: `useForm({ password: '', password_confirmation: '' })` → `form.post(route)`
    - shadcn/ui components: `Input`, `Button`, `Label` from `@/components/ui/`

    **WHY Each Reference Matters**:
    - `verify-email.tsx` is the closest existing pattern — guest auth page with AuthLayout
    - `register-form.tsx` shows how password fields are implemented with useForm in this codebase
    - AuthLayout shows the prop interface (title, description) to match visual consistency
    - Wayfinder import path will be auto-generated but follows predictable pattern

    **Acceptance Criteria**:
    - [ ] `resources/js/pages/auth/accept-invitation.tsx` exists and renders with AuthLayout
    - [ ] Page shows password and password_confirmation fields
    - [ ] Form submits via Inertia useForm POST to invitation.store route
    - [ ] `resources/js/pages/auth/invitation-expired.tsx` exists with friendly expiry message
    - [ ] `vendor/bin/sail npm run build` exits 0
    - [ ] No TypeScript errors

    **Agent-Executed QA Scenarios:**

    ```
    Scenario: Acceptance page renders with password form
      Tool: Playwright (playwright skill)
      Preconditions: Dev server running, invited user exists, valid signed URL generated
      Steps:
        1. Navigate to: the signed URL for the invited user
        2. Wait for: form visible (timeout: 5s)
        3. Assert: heading contains "Set Up" or "Welcome"
        4. Assert: input[name="password"] exists with type="password"
        5. Assert: input[name="password_confirmation"] exists with type="password"
        6. Assert: submit button visible
        7. Screenshot: .sisyphus/evidence/task-7-acceptance-page.png
      Expected Result: Clean password setup form renders
      Evidence: .sisyphus/evidence/task-7-acceptance-page.png

    Scenario: Expired link shows friendly error
      Tool: Playwright (playwright skill)
      Preconditions: Dev server running
      Steps:
        1. Navigate to: /invitation/accept/1?signature=invalid
        2. Wait for: page load (timeout: 5s)
        3. Assert: page contains text about "expired" or "invalid"
        4. Assert: NO password form visible
        5. Screenshot: .sisyphus/evidence/task-7-expired-page.png
      Expected Result: User sees helpful error, not raw 403
      Evidence: .sisyphus/evidence/task-7-expired-page.png

    Scenario: Full acceptance flow works end-to-end
      Tool: Playwright (playwright skill)
      Preconditions: Dev server running, invited user exists, valid signed URL
      Steps:
        1. Navigate to: valid signed URL
        2. Fill: input[name="password"] → "SecurePass123!"
        3. Fill: input[name="password_confirmation"] → "SecurePass123!"
        4. Click: submit button
        5. Wait for: navigation to /dashboard (timeout: 10s)
        6. Assert: URL is /dashboard
        7. Assert: page shows authenticated content (user name visible)
        8. Screenshot: .sisyphus/evidence/task-7-acceptance-success.png
      Expected Result: Password set, logged in, on dashboard
      Evidence: .sisyphus/evidence/task-7-acceptance-success.png
    ```

    **Commit**: YES
    - Message: `feat(users): add invitation acceptance and expiry pages`
    - Files: `resources/js/pages/auth/accept-invitation.tsx`, `resources/js/pages/auth/invitation-expired.tsx`
    - Pre-commit: `vendor/bin/sail npm run build`

---

- [ ]   8. Frontend: Users table status indicator + resend button

    **What to do**:
    - **`app/Features/UserManagement/Data/UserManagementData.php`**:
        - Add a computed boolean property `has_password` that derives from whether the user's password is null
        - This can be done as: `public bool $has_password` and populate it from the User model
        - Note: `password` is hidden on the model, so access it carefully — use `$user->getAttributeValue('password') !== null` or check `$user->password !== null` (hidden only affects serialization, not direct access)
    - **`resources/js/components/user-management/users-table.tsx`**:
        - Add a "Status" column showing "Active" (badge/text, green) or "Invited" (badge/text, amber/yellow) based on `has_password`
        - For users where `has_password === false`, add a "Resend Invitation" button/action in the row
        - The resend button should POST to `users.resend-invitation` route via Wayfinder (use `router.post()` from Inertia)
        - Add loading/disabled state while resend is in progress
        - Show success feedback after resend (flash message or brief toast)
    - Run `vendor/bin/sail npm run build`

    **Must NOT do**:
    - Do NOT add complex status state machine — just boolean has_password
    - Do NOT add a modal confirmation for resend (just a button click)
    - Do NOT add invitation date/time columns
    - Do NOT add bulk resend capability

    **Recommended Agent Profile**:
    - **Category**: `visual-engineering`
    - **Skills**: [`inertia-react-development`, `tailwindcss-development`, `wayfinder-development`]
        - `inertia-react-development`: Working with Inertia router.post for resend action
        - `tailwindcss-development`: Styling status badges and resend button
        - `wayfinder-development`: Importing resend-invitation route for the button action
    - **Skills Evaluated but Omitted**:
        - `pest-testing`: No PHP tests here (covered in Task 9)

    **Parallelization**:
    - **Can Run In Parallel**: YES
    - **Parallel Group**: Wave 4 (with Tasks 6, 7)
    - **Blocks**: Task 9
    - **Blocked By**: Tasks 2, 5

    **References**:

    **Pattern References**:
    - `resources/js/components/user-management/users-table.tsx:17-129` — Current table component. Add status column and resend action to existing structure
    - `app/Features/UserManagement/Data/UserManagementData.php:13-38` — Current DTO passed to table. Add `has_password` boolean property

    **API/Type References**:
    - `app/Models/User.php:52-56` — `$hidden` array includes `password`. Direct property access still works (`$user->password`), but serialization (toArray/toJSON) excludes it. The DTO must access the property directly, NOT rely on serialization
    - Wayfinder route for `users.resend-invitation` — import from `@/actions/...` for the resend POST

    **Documentation References**:
    - Inertia `router.post()` for non-form submissions (resend button)
    - shadcn/ui `Badge` component for status indicators
    - shadcn/ui `Button` with loading state for resend

    **WHY Each Reference Matters**:
    - Users table is the existing component to modify — need full context of current columns and actions
    - DTO shows how data flows from backend to frontend table
    - Model hidden array explains why we use a computed DTO property vs passing password directly
    - Wayfinder route needed for resend button action

    **Acceptance Criteria**:
    - [ ] `UserManagementData` has `has_password` boolean property
    - [ ] Users table shows status column: "Active" for users with password, "Invited" for users without
    - [ ] "Resend Invitation" button visible only for invited (no password) users
    - [ ] Resend button POSTs to `users.resend-invitation` route
    - [ ] Button shows loading state during request
    - [ ] `vendor/bin/sail npm run build` exits 0
    - [ ] `vendor/bin/sail bin pint --dirty --format agent` passes (for DTO change)

    **Agent-Executed QA Scenarios:**

    ```
    Scenario: Users table shows status indicators
      Tool: Playwright (playwright skill)
      Preconditions: Dev server running, admin logged in, at least one invited user and one active user exist
      Steps:
        1. Navigate to: /users
        2. Wait for: table visible (timeout: 5s)
        3. Assert: table has "Status" column header
        4. Assert: invited user row shows "Invited" badge/text
        5. Assert: active user row shows "Active" badge/text
        6. Screenshot: .sisyphus/evidence/task-8-users-table-status.png
      Expected Result: Status column clearly differentiates invited vs active
      Evidence: .sisyphus/evidence/task-8-users-table-status.png

    Scenario: Resend button visible for invited users only
      Tool: Playwright (playwright skill)
      Preconditions: Dev server running, admin logged in, both invited and active users exist
      Steps:
        1. Navigate to: /users
        2. Assert: invited user row has "Resend Invitation" button
        3. Assert: active user row does NOT have "Resend Invitation" button
        4. Screenshot: .sisyphus/evidence/task-8-resend-button-visibility.png
      Expected Result: Resend only available for pending users
      Evidence: .sisyphus/evidence/task-8-resend-button-visibility.png

    Scenario: Resend button works
      Tool: Playwright (playwright skill)
      Preconditions: Dev server running, admin logged in, invited user exists
      Steps:
        1. Navigate to: /users
        2. Click: "Resend Invitation" button for invited user
        3. Wait for: button to return to normal state (not loading) (timeout: 5s)
        4. Assert: success indicator visible (flash message, toast, or button text change)
        5. Screenshot: .sisyphus/evidence/task-8-resend-success.png
      Expected Result: Invitation re-sent, user sees confirmation
      Evidence: .sisyphus/evidence/task-8-resend-success.png
    ```

    **Commit**: YES
    - Message: `feat(users): add invitation status and resend to users table`
    - Files: `app/Features/UserManagement/Data/UserManagementData.php`, `resources/js/components/user-management/users-table.tsx`
    - Pre-commit: `vendor/bin/sail npm run build`

---

- [ ]   9. Tests + Wayfinder regeneration + final verification

    **What to do**:
    - **Wayfinder**: Run `vendor/bin/sail artisan wayfinder:generate` to regenerate TypeScript route functions for all new routes
    - **Create `tests/Feature/UserManagement/UserInvitationTest.php`** via `vendor/bin/sail artisan make:test --pest UserManagement/UserInvitationTest --no-interaction`:
        - Use same `beforeEach` pattern as `UserCrudTest.php` (create permissions, roles)
        - Use same `createAdmin()` and `createRegularUser()` helpers
        - **Test: admin creates user and invitation is sent**:
            - `Notification::fake()`, actingAs admin, POST `/users` with `{name, email, role}`
            - Assert redirect, assert user in DB with `password IS NULL`, assert `Notification::assertSentTo`
        - **Test: invited user can accept invitation via signed URL**:
            - Create invited user, generate signed URL, GET → assert 200 + Inertia page
            - POST with password → assert password set, email_verified_at set, Auth::check(), redirect /dashboard
        - **Test: expired signed URL shows error**:
            - Generate URL with past expiry, GET → assert Inertia error page (not raw 403)
        - **Test: tampered signed URL rejected**:
            - Modify signature in URL, GET → assert 403/error page
        - **Test: already-accepted user redirected on GET**:
            - User with password, valid signed URL, GET → redirect to login
        - **Test: already-accepted user redirected on POST**:
            - User with password, POST → redirect to login, password unchanged
        - **Test: acceptance validates password**:
            - POST with empty → errors, POST with short → errors, POST without confirmation → errors
        - **Test: admin can resend invitation**:
            - `Notification::fake()`, POST resend for invited user → notification sent
        - **Test: resend blocked for accepted user**:
            - POST resend for user with password → 422, no notification
        - **Test: non-admin cannot resend invitation**:
            - POST resend as regular user → 403
        - **Test: invited user cannot login with any password**:
            - POST `/login` with invited user's email → login fails
    - **Update `tests/Feature/UserManagement/UserCrudTest.php`** if not already done in Task 2:
        - Verify "admins can create a user" test posts WITHOUT password and asserts `password IS NULL`
        - Verify "store validates required fields" does NOT expect password error
    - **Final verification**:
        - `vendor/bin/sail artisan test --compact` — ALL tests pass
        - `vendor/bin/sail npm run build` — frontend builds cleanly
        - `vendor/bin/sail bin pint --dirty --format agent` — code style passes

    **Must NOT do**:
    - Do NOT create browser/E2E tests (Playwright scenarios in other tasks cover that)
    - Do NOT test Fortify registration or password reset flows (out of scope)
    - Do NOT create acceptance tests requiring manual email checking

    **Recommended Agent Profile**:
    - **Category**: `unspecified-high`
    - **Skills**: [`pest-testing`, `developing-with-fortify`, `wayfinder-development`]
        - `pest-testing`: Core skill — writing comprehensive Pest feature tests
        - `developing-with-fortify`: Understanding auth patterns (Auth::check, login assertions)
        - `wayfinder-development`: Running wayfinder:generate for route function regeneration
    - **Skills Evaluated but Omitted**:
        - `inertia-react-development`: No frontend changes in this task
        - `tailwindcss-development`: No styling work

    **Parallelization**:
    - **Can Run In Parallel**: NO
    - **Parallel Group**: Wave 5 (sequential, final)
    - **Blocks**: None (final task)
    - **Blocked By**: All previous tasks (1-8)

    **References**:

    **Pattern References**:
    - `tests/Feature/UserManagement/UserCrudTest.php` — FULL test file. Follow exact structure: `beforeEach` for permissions, `createAdmin()` helper, `actingAs()->post()` pattern, `assertDatabaseHas`, `assertRedirect`, `assertSessionHasErrors`

    **API/Type References**:
    - `app/Features/UserManagement/Notifications/InvitationNotification.php` — (Task 3) Class to assert with `Notification::assertSentTo`
    - Route names: `users.store`, `invitation.accept`, `invitation.store`, `users.resend-invitation`
    - `Illuminate\Support\Facades\URL::temporarySignedRoute` — for generating test URLs
    - `Illuminate\Support\Facades\Notification` — for `fake()` and `assertSentTo`
    - `Illuminate\Support\Facades\Auth` — for login assertions

    **Test References**:
    - `tests/Feature/UserManagement/UserCrudTest.php:36-92` — Authorization tests pattern (admin vs non-admin)
    - `tests/Feature/UserManagement/UserCrudTest.php:75-79` — Validation test pattern
    - `tests/Pest.php` — Pest configuration showing `RefreshDatabase` for Feature tests

    **WHY Each Reference Matters**:
    - UserCrudTest is the template — all new tests must follow its exact conventions
    - Route names are needed for `route()` helper in tests
    - URL facade is needed to generate signed URLs in tests
    - Notification facade is needed for faking and asserting notification dispatch

    **Acceptance Criteria**:
    - [ ] `tests/Feature/UserManagement/UserInvitationTest.php` exists with 10+ test cases
    - [ ] `vendor/bin/sail artisan wayfinder:generate` exits 0
    - [ ] ALL tests pass: `vendor/bin/sail artisan test --compact` (0 failures)
    - [ ] Frontend builds: `vendor/bin/sail npm run build` exits 0
    - [ ] Code style: `vendor/bin/sail bin pint --dirty --format agent` passes
    - [ ] No regressions in existing UserCrudTest

    **Agent-Executed QA Scenarios:**

    ```
    Scenario: Full test suite passes
      Tool: Bash
      Preconditions: All tasks 1-8 complete
      Steps:
        1. Run: vendor/bin/sail artisan wayfinder:generate
        2. Assert: exit code 0
        3. Run: vendor/bin/sail artisan test --compact
        4. Assert: 0 failures, 0 errors
        5. Capture: total test count and pass/fail summary
      Expected Result: All tests green
      Evidence: Test output captured

    Scenario: Frontend builds cleanly
      Tool: Bash
      Preconditions: All frontend tasks complete
      Steps:
        1. Run: vendor/bin/sail npm run build
        2. Assert: exit code 0
        3. Assert: no TypeScript errors in output
      Expected Result: Clean build with no errors
      Evidence: Build output captured

    Scenario: Code style passes
      Tool: Bash
      Preconditions: All PHP files modified
      Steps:
        1. Run: vendor/bin/sail bin pint --dirty --format agent
        2. Assert: exit code 0 or only auto-fixed formatting
      Expected Result: All files properly formatted
      Evidence: Command output captured
    ```

    **Commit**: YES
    - Message: `test(users): add comprehensive invitation flow tests`
    - Files: `tests/Feature/UserManagement/UserInvitationTest.php`, `tests/Feature/UserManagement/UserCrudTest.php` (if updated)
    - Pre-commit: `vendor/bin/sail artisan test --compact`

---

## Commit Strategy

| After Task | Message                                                          | Files                                                            | Verification                         |
| ---------- | ---------------------------------------------------------------- | ---------------------------------------------------------------- | ------------------------------------ |
| 1          | `feat(users): make password column nullable for invitation flow` | migration file                                                   | `artisan test --filter=UserCrudTest` |
| 2          | `refactor(users): remove password from admin user creation flow` | StoreUserRequest, Service, Controller, Factory, CrudTest         | `artisan test --filter=UserCrudTest` |
| 3          | `feat(users): add invitation notification with signed URL`       | InvitationNotification, StoreUserController                      | `artisan test --filter=UserCrudTest` |
| 4          | `feat(users): add invitation acceptance controllers and routes`  | 2 Controllers, Request, web.php, bootstrap/app.php, expired page | `artisan test --filter=UserCrudTest` |
| 5          | `feat(users): add resend invitation controller`                  | ResendInvitationController, web.php                              | `artisan test --filter=UserCrudTest` |
| 6          | `feat(users): remove password fields from create user form`      | create-user-form.tsx                                             | `npm run build`                      |
| 7          | `feat(users): add invitation acceptance and expiry pages`        | accept-invitation.tsx, invitation-expired.tsx                    | `npm run build`                      |
| 8          | `feat(users): add invitation status and resend to users table`   | UserManagementData.php, users-table.tsx                          | `npm run build` + `pint`             |
| 9          | `test(users): add comprehensive invitation flow tests`           | UserInvitationTest.php, CrudTest updates                         | `artisan test --compact`             |

---

## Success Criteria

### Verification Commands

```bash
vendor/bin/sail artisan test --compact                    # Expected: 0 failures
vendor/bin/sail artisan test --compact --filter=UserCrud  # Expected: 0 failures (regression check)
vendor/bin/sail artisan test --compact --filter=UserInvitation  # Expected: 0 failures (new tests)
vendor/bin/sail npm run build                              # Expected: exit 0
vendor/bin/sail bin pint --dirty --format agent            # Expected: exit 0
```

### Final Checklist

- [ ] All "Must Have" features present and working
- [ ] All "Must NOT Have" items absent (no new tables, no Fortify changes, etc.)
- [ ] All Pest tests pass (existing + new)
- [ ] Frontend builds without errors
- [ ] Code style passes (Pint)
- [ ] Wayfinder routes regenerated
