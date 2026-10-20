# How to contribute

**`main` is live.** Every push to `main` deploys to slackrescue.online. Never push to it directly.

## Workflow

1. Pull `main`, then create a branch: `git switch -c short-topic`.
2. Make your change. Setup is in the [README](README.md#local-development).
3. Check it:
   ```bash
   ./vendor/bin/sail test
   ./vendor/bin/sail bin pint
   ```
   Also click through the change on http://localhost:8000.
4. Push the branch and open a pull request. CI runs the tests.
5. Merge once CI is green and someone has looked at it.

## Editing text and images

Info pages are Markdown in `resources/content/`. Graphics go in `public/images/<page>/`. Small text fixes can be made in GitHub's web editor. Commit them to a new branch and open a pull request.

## Ground rules

- Keep it simple: no new packages or frameworks without talking first.
- Behaviour changes need a test. Text and styling changes don't.
- Never commit secrets, `.env` files, server details or personal data. Server settings live in GitHub variables and secrets.
- Report security problems privately to the maintainers, not in a public issue.
