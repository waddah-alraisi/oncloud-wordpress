# FORGE → the site → Deployments → Deploy Script: replace Forge's default with this (decision 10, 27 Oct 2026).
# Forge sets $FORGE_SITE_PATH, $FORGE_SITE_BRANCH, $FORGE_COMPOSER, $FORGE_PHP and $FORGE_PHP_FPM. The build is the one CI tests: the same
# composer.lock, the same check. A failed step stops the deploy and the live site keeps running the last good build.
set -e
cd "$FORGE_SITE_PATH"
git pull origin "$FORGE_SITE_BRANCH"

$FORGE_COMPOSER install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader

# The companion ships .po files; WordPress reads compiled .mo (needs gettext: sudo apt-get install -y gettext).
for po in web/app/mu-plugins/oncloud/languages/*.po; do
  if [ -f "$po" ]; then msgfmt -o "${po%.po}.mo" "$po"; fi
done

# The distribution's own rules: pins, allowlist, hardening, theme, and only allowed plugins installed.
$FORGE_PHP bin/check.php --installed

( flock -w 10 9 || exit 1
  echo 'Restarting FPM...'; sudo -S service "$FORGE_PHP_FPM" reload ) 9>/tmp/fpmlock
