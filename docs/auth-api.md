# Mobile + Password Auth API

Controller: `app/Http/Controllers/Api/PasswordAuthController.php`
Routes: `routes/api/auth.php`
Base URL: `{{base_url}}` (all paths below are under `/api/auth`)

Required headers on every request:

```
Accept: application/json
Content-Type: application/json
```

Notes:
- Phone is a Bangladesh mobile number (`01XXXXXXXXX`). `+8801…` / `8801…` are normalized.
- Password: minimum 8 characters, must be confirmed with `password_confirmation`.
- Protected endpoints use `Authorization: Bearer {{token}}` (Sanctum).
- The existing OTP login (`send-otp`, `resend-otp`, `verify-otp`) is unchanged.

---

## 1. Register

`POST /api/auth/register` — throttle 10/min

Request:
```json
{
  "name": "Test Customer",
  "phone": "01711000099",
  "password": "secret1234",
  "password_confirmation": "secret1234"
}
```

Success `201` (no token yet — the mobile number must be verified first; an OTP is sent by SMS, or written to `storage/logs/laravel.log` in `local`):
```json
{
  "success": true,
  "message": "Registration successful. Please verify your mobile number with the OTP sent.",
  "data": {
    "phone": "01711000099",
    "verification_required": true,
    "resend_after": 30,
    "expires_in": 300
  },
  "metadata": null
}
```

Validation error `422`:
```json
{
  "success": false,
  "message": "Registration failed. Please check your details.",
  "data": {
    "phone": ["This mobile number is already registered. Please login."],
    "password": ["The password field confirmation does not match."]
  },
  "metadata": null
}
```

---

## 1a. Verify Registration OTP

`POST /api/auth/register/verify` — throttle 10/min

Request:
```json
{
  "phone": "01711000099",
  "otp": "925667"
}
```

Success `200`: message `Mobile number verified successfully.`, `data` has `token`, `token_type`, `profile_complete`, `user` (with `phone_verified_at` set).

Errors:

| Code | Message |
|------|---------|
| 422 | `Please provide valid OTP details.` (validation) |
| 422 | `Invalid OTP code.` |
| 422 | `OTP expired or invalid. Please request a new one.` |
| 429 | `Too many invalid attempts. Please request a new OTP.` (after 5 wrong OTPs) |

## 1b. Resend Registration OTP

`POST /api/auth/register/resend-otp` — throttle 5/min

Request: `{ "phone": "01711000099" }`

Success `200` (same response for unknown/already verified numbers): message `If this number is pending verification, an OTP has been sent.`, `data` same as Register. Errors: `422` invalid phone, `429` `Please wait N seconds before requesting another OTP.`, `500` send failure.

---

## 2. Login

`POST /api/auth/login` — throttle 10/min

Request:
```json
{
  "phone": "01711000099",
  "password": "secret1234"
}
```

Success `200`: same `data` shape as Register, message `Login successful.`

Errors:

| Code | Message |
|------|---------|
| 401 | `Invalid mobile number or password.` |
| 403 | `Your account is not active. Please contact support.` |
| 403 | `Mobile number is not verified. Please verify with the OTP.` (`data`: `{phone_verified: false, phone}`; use register/resend-otp then register/verify) |
| 422 | `Mobile number and password are required.` (with field errors) |
| 429 | `Too many failed attempts. Try again in N minute(s).` |

Lockout: 5 wrong passwords lock the account for 15 minutes. A successful login or password reset clears it.

---

## 3. Forgot Password (send OTP)

`POST /api/auth/forgot-password` — throttle 5/min

Request:
```json
{
  "phone": "01711000099"
}
```

Success `200` (identical whether or not the number is registered, to avoid revealing accounts):
```json
{
  "success": true,
  "message": "If this number is registered, an OTP has been sent.",
  "data": {
    "phone": "01711000099",
    "resend_after": 30,
    "expires_in": 300
  },
  "metadata": null
}
```

Errors:

| Code | Message |
|------|---------|
| 422 | `Valid mobile number is required.` |
| 429 | `Please wait N seconds before requesting another OTP.` |
| 500 | `Failed to send OTP. Please try again.` |

In the `local` environment the OTP is written to `storage/logs/laravel.log` instead of sent by SMS. OTP expires in 5 minutes.

---

## 4. Reset Password

`POST /api/auth/reset-password` — throttle 10/min

Request:
```json
{
  "phone": "01711000099",
  "otp": "925667",
  "password": "newsecret1234",
  "password_confirmation": "newsecret1234"
}
```

Success `200`:
```json
{
  "success": true,
  "message": "Password reset successful. Please login with your new password.",
  "data": null,
  "metadata": null
}
```

Errors:

| Code | Message |
|------|---------|
| 422 | `Please provide valid details.` (validation) |
| 422 | `Invalid OTP code.` |
| 422 | `OTP expired or invalid. Please request a new one.` |
| 429 | `Too many invalid attempts. Please request a new OTP.` (after 5 wrong OTPs) |

On success all existing tokens for the user are revoked, the phone is marked verified, and any login lockout is cleared.

---

## Typical flows

- **Register:** `register` → `register/verify` (OTP) → use returned token. Unverified users: `register/resend-otp` → `register/verify`.
- **Login:** `login` → use returned token.
- **Forgot password:** `forgot-password` → `reset-password` → `login`.

## Behaviour notes

- Register rejects any phone that already has a user row, including placeholder rows created by `send-otp` for unfinished OTP logins.
- Users created via OTP login only have a random password; they can set one via forgot-password.

## Postman

Requests are in `postman/Agonito_API.postman_collection.json` under **01 - Authentication**. Regenerate with `php scripts/generate-postman-collection.php`. Register and Login store the token in `{{token}}`.
