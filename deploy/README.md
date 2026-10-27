# deploy/ — an OnCloud WordPress server in production

Decision 10 (`oncloud-documentations/planned/wordpress-platform.md` §5): **DigitalOcean servers managed by Laravel
Forge** (27 Oct 2026). Forge runs nginx, PHP-FPM, MySQL and Redis on the server and deploys this repository with
Composer, from the same `composer.lock` CI tests.

- `forge/deploy.sh`: the site's deploy script (Forge → the site → Deployments).
- `forge/nginx-rules.conf`: the lines added to the site's nginx configuration.
- `forge/cron.sh`: WordPress's scheduled tasks, run every minute by Forge's Scheduler.
- The site's settings are in `.env` at the site's root (Forge → the site → Environment), read by `config/dotenv.php`.
  The template is `.env.production.example`.

Step by step: `oncloud-documentations/oncloud-wordpress/runbooks/forge-shared-network.md`.

**Docker alternative.** `compose.production.yaml` and `Caddyfile` run the same build from the CI images on a plain Docker
droplet (0.6.0). Forge is the chosen way; the Docker files stay as the tested fallback and for local runs.
