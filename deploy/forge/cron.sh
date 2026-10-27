#!/bin/sh
# FORGE → the server → Scheduler: a new job, user `forge`, every minute (decision 10, 27 Oct 2026):
#   sh /home/forge/sites.oncloud.om/deploy/forge/cron.sh /home/forge/sites.oncloud.om
# WordPress's own cron is off (DISABLE_WP_CRON outside local): this runs what is due on every site of the network,
# or on the one site of a Private Store.
set -eu
cd "${1:?the site path, e.g. /home/forge/sites.oncloud.om}"
wp="php vendor/bin/wp --path=web/wp --quiet"
if $wp core is-installed --network 2>/dev/null; then
  for url in $($wp site list --field=url --deleted=0 --archived=0 --spam=0); do
    $wp cron event run --due-now --url="$url" || true
  done
else
  $wp cron event run --due-now
fi
