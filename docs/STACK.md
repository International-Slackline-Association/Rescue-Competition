# Stack decision — Slack Rescue Competition

Status: accepted, scaffold implemented · 2026-10-19

## TL;DR

**Laravel 13 + Filament 5 + Laravel Boost / Laravel MCP, on MySQL, deployed as a pre-built artifact to plain PHP webhosting.**
Info pages are Markdown files in the repo. Login is OIDC against the ISA Cognito user pool.

Everything is MIT-licensed. An LLM can manage all of it: the code (Boost), the data (an MCP server inside the app), and the content (Markdown in git).

## Requirements recap

| Need | Shape |
|---|---|
| Info pages (rules, about) | few, rarely change → content |
| Rescues: image + involved ISA users with roles | relational data, many-to-many |
| Ranking per role (victim / line rescuer / ground rescuer) | aggregate SQL query |
| Profile: all rescues of a person | join |
| Users create/edit own rescues, admin deletes | per-record authorization |
| Auth via ISA accounts | external OIDC (AWS Cognito) |
| Open source, cheap PHP webhosting, LLM-manageable | constraints |

This is mostly a **small data app with a few content pages**, not a content site. That decides the stack.

## Options evaluated

| | License | Shared hosting | LLM integration | Fit for relational app data | Verdict |
|---|---|---|---|---|---|
| **Laravel + Filament** | MIT | yes (ship `vendor/` built in CI) | **official** `laravel/boost` (dev MCP: schema, routes, docs, tinker) + `laravel/mcp` (expose own tools) | native (Eloquent, policies, migrations) | ✅ **pick** |
| WordPress 7.0 | GPL | best (works anywhere) | core Abilities API + AI Client, official MCP Adapter plugin | weak: rescues as CPT + postmeta, user↔rescue roles awkward, ranking = custom SQL anyway | runner-up |
| Grav 2.0 | MIT | yes, flat-file | official `grav-mcp` (70 tools; pages, users, media) | no MySQL, no relations; rescues would be custom Flex objects not covered by MCP | good for content-only sites |
| Drupal 11 / CMS 2 | GPL | heavy, Composer + Drush | MCP server module still alpha | good but large | overkill |
| Statamic 6 | **proprietary** (source-available) | yes | community MCP + Boost | good | ❌ not open source |
| Kirby | **proprietary** | yes | community | ok | ❌ not open source |

Why not WordPress: it's the easiest to host, but this domain fights its data model. You'd end up with a custom plugin, custom tables and custom SQL. At that point WordPress is a 50 MB dependency wrapped around a small app. Its MCP mainly exposes *content*. With Laravel the LLM sees the *whole app*: schema, policies and tests.

## Architecture

```
            ┌───────────────────────────┐
 browser ──►│ Laravel (Blade + Tailwind)│──► MySQL
            │  ├ public pages (Markdown)│
            │  ├ /rescues, /ranking,    │──► storage/app/public (images)
            │  │  /people/{isaId}       │
            │  ├ /admin  (Filament)     │
            │  └ /mcp    (laravel/mcp)  │◄── LLM agents (token auth, admin only)
            └────────────┬──────────────┘
                         │ OIDC code flow (Socialite + Cognito provider)
                         ▼
            auth.slacklineinternational.org (ISA Cognito pool, eu-central-1)
```

- **Frontend:** server-rendered Blade + Tailwind, built once in CI. Add Alpine only for small interactions such as the people picker. No SPA: there's nothing app-like enough to justify one.
- **Admin:** Filament panel at `/admin` (admins only), used for moderation and deletes. Normal users manage their rescues in the public UI, which is simpler than giving them a panel.
- **Content pages:** `resources/content/*.md` (front-matter + Markdown), rendered through one route. They're edited in git by people or LLMs, and reviewed in PRs. No page builder needed.

## Data model

```
users            id, isa_id (unique, e.g. ISA_1A2B3C4D), cognito_sub, name, email,
                 avatar_url, is_admin, timestamps
rescues          id, created_by → users, happened_on, location, description,
                 image_path, timestamps
rescue_user      rescue_id, user_id, role ENUM('victim','line_rescuer','ground_rescuer')
                 PK(rescue_id, user_id, role)
```

- Ranking = `SELECT user_id, COUNT(*) FROM rescue_user WHERE role=? GROUP BY user_id ORDER BY 2 DESC`. Swap in points later if the rules call for it.
- A profile is the user's `rescue_user` rows joined to `rescues`.
- **Authorization (one policy):** a user can view any rescue, can update if they're one of its participants, and only admins can delete.

## Auth — ISA users

Facts from [isa-users](https://github.com/International-Slackline-Association/isa-users):

- AWS Cognito user pool `eu-central-1_iGaYGKeyJ`, hosted UI at `auth.slacklineinternational.org`, standard OIDC (`openid email` scopes, authorization-code flow).
- ISA ID = `ISA_` + first 8 hex characters of sha256(cognito username), uppercased (`generateISAIdFromUsername`). We compute the same ID locally.
- Name and avatar come from `GET /basic/userDetails` on the ISA API, called with the user's ID token.
- **The ISA API has no user search or list endpoint.**

Consequences:

1. **ISA must register a new Cognito app client** for this site, with our callback URL. That's the one external dependency. Ask ISA early.
2. Login: Socialite + `socialiteproviders/cognito`. On callback, upsert `users` by `cognito_sub`, then fetch name and avatar.
3. **Picking "involved people":** only people who have logged in at least once are searchable, from the local `users` table. To add someone who hasn't logged in yet, enter their ISA ID or email. This creates a placeholder user that gets claimed when they first log in (matched by ISA ID or email).

## LLM integration (the "fully manageable" part)

| Layer | How the LLM works on it |
|---|---|
| Code | `laravel/boost`: an MCP server for Claude Code, Cursor and others. Gives version-matched Laravel/Filament docs, DB schema, routes, logs and tinker, plus AI guidelines in the repo |
| Data | `laravel/mcp` server at `/mcp` (`app/Mcp`) with four typed tools: `list_rescues`, `ranking`, `delete_rescue`, `merge_users`. Admins only. Each tool goes through the same `RescuePolicy` / `admin` gate as the web UI and Filament |
| Content | Markdown in git: the LLM opens a PR, a human merges |
| Ops | GitHub Actions: test → build → deploy. Every change goes through git, so it's reviewable and reversible |

### MCP auth: Sanctum personal access tokens

The `/mcp` route uses `auth:sanctum` plus the `can:admin` gate. An admin creates a token for themselves on their own user page in Filament ("New MCP token", shown once; a new token revokes the old one). MCP clients send it as `Authorization: Bearer <token>`.

Why Sanctum and not Passport/OAuth 2.1 (which the MCP spec prefers):

- Sanctum is one migration (`personal_access_tokens`) and no keys, so it works on shared PHP hosting with nothing to configure. Passport needs RSA keys on the server, several OAuth tables and client registration routes.
- Only a handful of admins use this, and every MCP client we care about (Claude Code, Claude Desktop via `mcp-remote`, Cursor) accepts a bearer header.
- Tokens are hashed in the database. Revoking admin rights blocks the token at once, because the gate is checked on every request.

Switch to Passport (`Mcp::oauthRoutes()`) only if we need a client that supports OAuth alone.

## Hosting & deploy

Needs: PHP ≥ 8.3, MySQL/MariaDB, the ability to point the document root at `public/` (or a subfolder plus `.htaccess` rewrite), and SSH **or** FTP.

- CI runs `composer install --no-dev -o`, `npm ci && npm run build`, and tests, then uploads the artifact (rsync over SSH, or FTP deploy action).
- Composer and Node aren't needed on the host.
- Run migrations over SSH if available. Without SSH, use a protected one-off deploy route.
- Images go to local `storage/` (symlink `public/storage`), max 8 MB. Resizing on upload is not implemented yet. Move to S3 later if needed (one line of config).

## Repo layout (planned)

```
app/  (Models, Policies, Http/Controllers, Filament/, Mcp/)
routes/ai.php        the /mcp server
database/migrations/
resources/content/   rules.md, about.md
resources/views/
docs/STACK.md
.github/workflows/deploy.yml
```

## Open questions

1. Will ISA register a Cognito app client, and which callback domain will this site use?
2. Scoring: does every rescue count 1 point per role, or are there weights or a season or date window?
3. Does a rescue need approval before it counts (moderation queue), or does it go live straight away and admins only delete?
4. Can one person hold two roles in the same rescue? The model allows it; the rules should decide.
5. Hosting provider: confirm PHP 8.3+, SSH access, and document-root control.
