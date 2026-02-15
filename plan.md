# Refactoring Plan

## Phase 1: Feature-Based Structure

Reorganize from technical-layer to vertical-slice architecture.

### 1.1 Create feature directories

```
app/
  Features/
    Auth/
      Actions/
      Concerns/
    Settings/
      Actions/
      Controllers/
      Requests/
      Services/
```

### 1.2 Move Auth code into `Features/Auth/`

- Move `app/Actions/Fortify/CreateNewUser.php` to `app/Features/Auth/Actions/`
- Move `app/Actions/Fortify/ResetUserPassword.php` to `app/Features/Auth/Actions/`
- Move `app/Concerns/PasswordValidationRules.php` to `app/Features/Auth/Concerns/`
- Move `app/Concerns/ProfileValidationRules.php` to `app/Features/Auth/Concerns/`
- Update `FortifyServiceProvider` references
- Remove empty `app/Actions/` and `app/Concerns/` directories

### 1.3 Move Settings code into `Features/Settings/`

- Move `app/Http/Controllers/Settings/*` to `app/Features/Settings/Controllers/`
- Move `app/Http/Requests/Settings/*` to `app/Features/Settings/Requests/`
- Update route registrations and namespace imports

---

## Phase 2: Skinny Controllers

Extract business logic from controllers into Actions and Services.

### 2.1 Create `ProfileService`

Location: `app/Features/Settings/Services/ProfileService.php`

Methods:

- `update(User $user, ProfileData $data): User` — fill model, reset email verification if email changed, save
- `delete(User $user): void` — delete the user record

### 2.2 Create `DeleteProfileAction`

Location: `app/Features/Settings/Actions/DeleteProfileAction.php`

Orchestrates the full account deletion workflow:

- Logout via `Auth::logout()`
- Delete user via `ProfileService::delete()`
- Invalidate session and regenerate token

### 2.3 Create `PasswordService`

Location: `app/Features/Settings/Services/PasswordService.php`

Methods:

- `update(User $user, string $password): User` — update password

### 2.4 Refactor controllers

- `ProfileController::update()` — delegate to `ProfileService::update()`
- `ProfileController::destroy()` — delegate to `DeleteProfileAction::handle()`
- `PasswordController::update()` — delegate to `PasswordService::update()`

---

## Phase 3: Fortify Actions Cleanup

### 3.1 Rename classes (where Fortify contracts allow)

- `CreateNewUser` cannot be renamed to `CreateNewUserAction` or use `handle()` — Fortify contract requires the `create()` method signature. Leave method name as-is but add a note.
- Same for `ResetUserPassword` and its `reset()` method.

### 3.2 Add constructor dependency injection

Replace trait usage with injected dependencies where possible. Extract validation into the Fortify-provided validation hooks or keep inline (Fortify pattern).

---

## Phase 4: Authorization Fix

### 4.1 Move feature-flag check out of FormRequest

- `TwoFactorAuthenticationRequest::authorize()` currently checks `Features::enabled(...)`. Move this to middleware or a Gate check in `TwoFactorAuthenticationController::show()`.
- Change `authorize()` to return `true` (or remove it entirely).

---

## Phase 5: Extract Page Components

Extract form markup and logic from page components into dedicated feature components.

### 5.1 Auth pages

| Page                                  | Extract to                                      |
| ------------------------------------- | ----------------------------------------------- |
| `pages/auth/login.tsx`                | `components/auth/login-form.tsx`                |
| `pages/auth/register.tsx`             | `components/auth/register-form.tsx`             |
| `pages/auth/forgot-password.tsx`      | `components/auth/forgot-password-form.tsx`      |
| `pages/auth/reset-password.tsx`       | `components/auth/reset-password-form.tsx`       |
| `pages/auth/confirm-password.tsx`     | `components/auth/confirm-password-form.tsx`     |
| `pages/auth/verify-email.tsx`         | `components/auth/verify-email-form.tsx`         |
| `pages/auth/two-factor-challenge.tsx` | `components/auth/two-factor-challenge-form.tsx` |

### 5.2 Settings pages

| Page                          | Extract to                                     |
| ----------------------------- | ---------------------------------------------- |
| `pages/settings/profile.tsx`  | `components/settings/update-profile-form.tsx`  |
| `pages/settings/password.tsx` | `components/settings/update-password-form.tsx` |

### 5.3 Welcome page

Break `pages/welcome.tsx` (~500 lines) into composed components:

- `components/welcome/hero-section.tsx`
- `components/welcome/navigation.tsx`
- `components/welcome/footer-links.tsx`
- Inline SVGs into separate component files or an assets directory

---

## Phase 6: Replace Raw Tailwind Colors with Design Tokens

### 6.1 Define missing semantic tokens

If `text-success` or `text-destructive` tokens don't exist yet, define them in the CSS variables / Tailwind config.

### 6.2 Replace raw colors across components

| Raw Class                                  | Replace With                        |
| ------------------------------------------ | ----------------------------------- |
| `text-red-600`, `dark:text-red-400`        | `text-destructive`                  |
| `text-green-600`                           | `text-success` (define if needed)   |
| `text-neutral-600`, `text-neutral-500`     | `text-muted-foreground`             |
| `bg-neutral-200`, `dark:bg-neutral-700`    | `bg-muted`                          |
| `bg-neutral-100`, `dark:bg-neutral-800`    | `bg-accent` or `bg-muted`           |
| `border-red-100`, `dark:border-red-200/10` | `border-destructive` (with opacity) |
| `bg-red-50`, `dark:bg-red-700/10`          | `bg-destructive/10`                 |

Files to update: `appearance-tabs.tsx`, `app-header.tsx`, `nav-footer.tsx`, `delete-user.tsx`, `input-error.tsx`, `user-info.tsx`, `text-link.tsx`, and all auth/settings pages.

### 6.3 Replace arbitrary hex values in `welcome.tsx`

Replace all 19+ arbitrary hex values (`bg-[#FDFDFC]`, `text-[#1b1b18]`, etc.) with design tokens.

---

## Phase 7: Auto-Generate TypeScript Types

### 7.1 Configure and run typescript-transformer

Run `php artisan typescript:transform` to generate types from `spatie/laravel-data` classes.

### 7.2 Replace hand-written types

Remove manually defined types from `resources/js/types/auth.ts`:

- `User` — replace with generated type
- `Auth` — replace with generated type
- `TwoFactorSetupData` — replace with generated type
- `TwoFactorSecretKey` — replace with generated type

Create the corresponding `Data` classes on the backend if they don't exist yet.

### 7.3 Keep frontend-only types

`resources/js/types/navigation.ts` and `resources/js/types/ui.ts` are correctly hand-written and should remain.

---

## Phase 8: Minor Fixes

### 8.1 Use `cn()` for conditional class merging

- `components/two-factor-recovery-codes.tsx` — replace template literal with `cn()`
- `components/heading.tsx` — replace ternary with `cn()`
- `components/nav-footer.tsx` — replace template literal with `cn()`

---

## Execution Order

Phases are ordered by dependency and risk:

1. **Phase 5** (Extract page components) — no backend changes, low risk
2. **Phase 6** (Design tokens) — styling only, low risk
3. **Phase 8** (Minor fixes) — trivial, low risk
4. **Phase 2** (Skinny controllers) — create Services/Actions before moving files
5. **Phase 1** (Feature structure) — namespace changes, update all imports
6. **Phase 3** (Fortify cleanup) — constrained by Fortify contracts
7. **Phase 4** (Authorization fix) — small targeted change
8. **Phase 7** (TypeScript types) — requires backend Data classes to exist first

Each phase should be followed by running `php artisan test --compact` and `npm run build` to verify nothing breaks.
