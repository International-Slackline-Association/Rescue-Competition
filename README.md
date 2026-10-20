# Slack Rescue Competition

Website for the International Slackline Association's rescue practice competition. People log in with their ISA account, submit practice rescues with a proof photo and everyone involved, and climb the ranking for their role (victim, on-the-line rescuer, ground rescuer).

Stack: Laravel 13 · Filament 5 (admin) · Blade + Tailwind 4 · MySQL. See [docs/STACK.md](docs/STACK.md) for why.

Want to help? Read [CONTRIBUTING.md](CONTRIBUTING.md) first: `main` is live.

## Local development

Requires Docker. Everything runs in [Sail](https://laravel.com/docs/sail) containers.

```bash
cp .env.example .env
docker run --rm -u "$(id -u):$(id -g)" -v "$PWD:/app" -w /app composer:2 install --ignore-platform-reqs
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail artisan storage:link
./vendor/bin/sail npm install && ./vendor/bin/sail npm run dev
```

Open http://localhost:8000. While `COGNITO_CLIENT_ID` is empty, **Log in** shows a local account picker instead of the ISA login. The seeder creates **Test Admin** (admin) and **Test Member** (regular user) for this, plus some demo people. The admin panel is at `/admin`. The picker only works when `APP_ENV=local`.

```bash
./vendor/bin/sail test        # feature tests
./vendor/bin/sail bin pint    # code style
```

## Where things are

| What | Where |
|---|---|
| Info pages (home, rules, graphics) | `resources/content/*.md`: add a file and it becomes a page |
| Images under an info page | `public/images/<page>/` (e.g. `rescue_graphics/`): drop in JPG/PNG/WebP/SVG files, shown in file-name order, `01-line-rescue.jpg` → caption "Line rescue". A PDF with the same name gets a download link (PDFs alone are not shown) |
| Rescues, participants, roles | `app/Models`, `app/Enums/RescueRole.php`, `database/migrations` |
| Who may edit/delete | `app/Policies/RescuePolicy.php` |
| ISA login | `app/Http/Controllers/AuthController.php`, `config/services.php` |
| Admin panel | `app/Filament` |
| MCP server for admins (`/mcp`) | `app/Mcp`, `routes/ai.php` |
| CI + deploy | `.github/workflows/ci.yml` |

## ISA login

Login is OIDC against the ISA Cognito user pool (`auth.slacklineinternational.org`). ISA has to register an app client for this site with the callback URL `https://slackrescue.online/auth/callback`. Then set `COGNITO_CLIENT_ID` and `COGNITO_CLIENT_SECRET` in `.env`. Step by step: [docs/ISA-LOGIN.md](docs/ISA-LOGIN.md).

People can be added to a rescue before they have ever logged in, by ISA ID or email. They get a placeholder account that is claimed on their first login.

## Deploy

`main` is protected (squash-merged pull requests only, CI must pass). Every merge to `main` runs the tests, then build the app (vendor + assets) in CI and rsync it over SSH to the repository variable `DEPLOY_PATH` (relative to the SSH home). It uses the hosting account from the repository variables `FTP_HOST`, `FTP_USER` and the secret `FTP_PASSWORD`. The host needs PHP ≥ 8.3 with intl, MySQL, and its document root pointed at `DEPLOY_PATH/public`. `.env` is created once on the server.

First deploy, on the server (`DEPLOY_PATH`, over SSH with the same account):

1. Create `.env` from `.env.example` with at least `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://slackrescue.online`, `SESSION_SECURE_COOKIE=true`, a fresh `APP_KEY` (`php artisan key:generate --show` locally), the MySQL credentials and the Cognito client. Never run `migrate --seed` here: the seeder is demo data.
2. Merge a pull request into `main`; CI uploads, migrates and caches.
3. Log in once with your ISA account, then make yourself admin: `php artisan admin:grant you@example.org` (email or ISA ID). Further admins can be set in `/admin`.

`public/.user.ini` raises PHP's upload limit to 10 MB for rescue photos; if the host ignores it, set `upload_max_filesize` and `post_max_size` in its PHP settings.

## Working with AI agents

[Laravel Boost](https://laravel.com/docs/boost) is installed. `.mcp.json` gives Claude Code (and other MCP clients) access to the app's schema, routes, logs and version-matched docs. Agent guidelines live in `CLAUDE.md` / `AGENTS.md`, and skills in `.claude/skills`.

The app has its own MCP server at `/mcp` so admins can manage data from an LLM client. It has four tools: `list_rescues`, `ranking`, `delete_rescue` and `merge_users` (merges a placeholder user into their real account). To use it, open your own user in `/admin` and click **New MCP token**. Then add the server to your client, for example:

```bash
claude mcp add --transport http rescue https://slackrescue.online/mcp --header "Authorization: Bearer <token>"
```

Only admins can use it, and every tool goes through the same permission checks as the website. See [docs/STACK.md](docs/STACK.md#mcp-auth-sanctum-personal-access-tokens) for why it uses Sanctum tokens.
