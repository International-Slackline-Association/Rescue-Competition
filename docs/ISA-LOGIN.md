# ISA login — AWS Cognito setup

The site logs people in with their ISA account: an OIDC authorization-code flow against the ISA Cognito user pool. Nothing has to be *installed* on AWS. The only work is **one new app client in the existing ISA user pool**, plus filling in `.env`.

## What the app expects

| | Value |
|---|---|
| User pool | `eu-central-1_iGaYGKeyJ` (`isa-users`, region `eu-central-1`) |
| Hosted login domain | `https://auth.slacklineinternational.org` (`COGNITO_HOST`) |
| Flow | authorization code, confidential client (with secret) |
| Scopes | `openid email` |
| Callback URL | `https://slackrescue.online/auth/callback` |
| Sign-out URL | `https://slackrescue.online` (equal to `APP_URL`, no trailing slash) |
| Claims used | `sub`, `username` (→ ISA ID), `email`, `email_verified` |
| Name and avatar | `GET https://account-api.slacklineinternational.org/basic/userDetails`, called with the user's **ID token** |

Code: `app/Http/Controllers/AuthController.php`, `config/services.php` (`cognito`, `isa`). The Socialite provider (`socialiteproviders/cognito`) calls `{host}/oauth2/authorize`, `/oauth2/token`, `/oauth2/userInfo` and `/logout`.

## Current state of the pool (checked 2026-10-19)

- **Identity providers:** only `COGNITO` (email + password). There is no Google, Apple or other social login.
- **Domain:** `auth.slacklineinternational.org` uses the classic hosted UI (`ManagedLoginVersion: 1`), so a new client needs no branding style.
- **Clients are created by hand:** the isa-users repo has no app client definitions in code (Serverless only deploys the API and Lambda triggers), so a client made with the CLI or console isn't overwritten by an isa-users deploy.
- **Existing clients** use code flow with only `ALLOW_REFRESH_TOKEN_AUTH` and `PreventUserExistenceErrors`. Always set read and write attributes explicitly: leaving them unset means *all* attributes.
- **Required user attributes:** `email`, `name`, `family_name`. The pool also has `custom:ISA_ID` and `custom:identityType`.
- **ISA API (`isa-users-prod`):** uses a `COGNITO_USER_POOLS` authorizer on the whole pool, so it accepts an ID token from our new client without any change. It reads only `cognito:username` (→ ISA ID), `email` and `sub` from the token.

## Attribute access: least privilege

### Read: `email`, `email_verified`

That covers everything the site and the ISA API need. `sub` and `username` are always in the tokens and don't need to be listed. Leaving the list unset would put every readable attribute (phone, address, birthdate, gender, …) in our ID token. With this list the token carries only these two.

Name and avatar come from the ISA API, not from Cognito, so `name` and `family_name` aren't needed.

### Write: `email`, `name`, `family_name`, the smallest set Cognito accepts

A client can't be created with fewer: Cognito requires every *required* attribute to be writable (`Invalid write attributes specified while creating a client`). The hosted UI's **Sign up** form, which is also reachable from our login page, writes them. The existing ISA clients use the same three.

Write access through our client is effectively limited to the hosted UI sign-up, because:

- Changing your own attributes (`UpdateUserAttributes`) needs an access token with the `aws.cognito.signin.user.admin` scope. Our client doesn't allow that scope, so its tokens can't change anything.
- `ExplicitAuthFlows` is only `ALLOW_REFRESH_TOKEN_AUTH`. Nobody can call `InitiateAuth` with a password against our client to get an all-powerful token.
- The direct `SignUp` API needs the client secret (`SECRET_HASH`), which lives only on our server. Anyone can already sign up through the `account` client anyway.
- The app itself never writes to Cognito.

Unset values are the dangerous default here: **always pass `--read-attributes` and `--write-attributes` explicitly.**

### Tokens

The app uses the tokens once, at login, then relies on its own session. It never stores the refresh token. Keep the refresh token's lifetime at the minimum (60 minutes) so a leaked token is of little use.

## Todo

### 1. Log in to AWS

```powershell
aws sso login --profile <your-isa-aws-profile>
```

### 2. Create the app client

PowerShell:

```powershell
aws cognito-idp create-user-pool-client --profile <your-isa-aws-profile> --region eu-central-1 `
  --user-pool-id eu-central-1_iGaYGKeyJ `
  --client-name slack-rescue-competition `
  --generate-secret `
  --allowed-o-auth-flows-user-pool-client `
  --allowed-o-auth-flows code `
  --allowed-o-auth-scopes openid email `
  --callback-urls https://slackrescue.online/auth/callback `
  --logout-urls https://slackrescue.online `
  --supported-identity-providers COGNITO `
  --read-attributes email email_verified `
  --write-attributes email name family_name `
  --explicit-auth-flows ALLOW_REFRESH_TOKEN_AUTH `
  --prevent-user-existence-errors ENABLED `
  --enable-token-revocation `
  --refresh-token-validity 60 `
  --token-validity-units RefreshToken=minutes
```

(In bash, replace the backticks with `\`.)

- [ ] Optional, for testing the real login locally: add `http://localhost:8000/auth/callback` to `--callback-urls` and `http://localhost:8000` to `--logout-urls`. Cognito allows plain http only for `localhost`. Leave these out in production.
- [ ] Copy `ClientId` and `ClientSecret` from the output. You can show the secret again later with `aws cognito-idp describe-user-pool-client --profile <your-isa-aws-profile> --region eu-central-1 --user-pool-id eu-central-1_iGaYGKeyJ --client-id <id> --query UserPoolClient.ClientSecret`.

Updating a client later with `update-user-pool-client` resets every parameter you leave out to its default (for example, read and write attributes go back to "all"). Always pass the full set of parameters again.

### 3. Configure the site

On the server, in `.env`:

```dotenv
APP_URL=https://slackrescue.online
COGNITO_CLIENT_ID=<new-client-id>
COGNITO_CLIENT_SECRET=<secret>
# defaults, only set if different:
# COGNITO_HOST=https://auth.slacklineinternational.org
# COGNITO_REDIRECT_URI=${APP_URL}/auth/callback
# COGNITO_LOGOUT_URI=${APP_URL}
# ISA_API_URL=https://account-api.slacklineinternational.org
```

Then `php artisan config:cache`. Store the secret only in the server `.env` (and a password manager), never in git.

### 4. Verify

- [ ] Check the client: `aws cognito-idp describe-user-pool-client ... --client-id <id>` shows exactly the read and write attributes above, `AllowedOAuthScopes` = `email, openid`, and no `aws.cognito.signin.user.admin`.
- [ ] **Log in** goes to `auth.slacklineinternational.org`, and after logging in you land on your own profile page.
- [ ] Your ISA ID on the profile matches the one ISA shows you.
- [ ] Name and avatar are filled in, which means the ISA API accepted the ID token.
- [ ] **Log out** goes through Cognito and comes back to the home page.
- [ ] Grant the first admin: `php artisan admin:grant you@example.org`.

## Common errors

| Error | Cause |
|---|---|
| `redirect_mismatch` on login | Callback URL in Cognito ≠ `APP_URL` + `/auth/callback` (check https and the host) |
| `invalid_client` on callback | Wrong secret, or the client was created without `--generate-secret` |
| `invalid_scope` | `openid`/`email` not in the client's allowed scopes |
| Logout error page | Sign-out URL in Cognito ≠ `APP_URL` |
| `Invalid write attributes specified while creating a client` | `--write-attributes` is missing a required attribute (`email`, `name`, `family_name`) |
| Name falls back to email prefix | ISA API call failed (network, or the API changed) |
