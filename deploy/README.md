# deploy/ — an OnCloud WordPress server in production

Decision 10 (`oncloud-documentations/planned/wordpress-platform.md` §5): DigitalOcean droplets running Docker.

- `compose.production.yaml` runs the server: Caddy (HTTPS), nginx, php, MariaDB and Valkey.
  - The php and nginx images come from ghcr.io, at `ONCLOUD_IMAGE_TAG`.
- `Caddyfile` issues a certificate per host on demand. Before each one it checks `web/oncloud-tls.php`, so only our own hosts get certificates.
- `.env.production.example` is the settings template. Copy it to `deploy/.env` on the server; it is never committed.

The step-by-step set-up of the shared network (`oncloud-sites-1`) is in
`oncloud-documentations/oncloud-wordpress/runbooks/shared-network-install.md`.
