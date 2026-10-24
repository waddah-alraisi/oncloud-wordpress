#!/bin/sh
# Install a fresh OnCloud site in a running `php` container (ADR 0052):
#   docker compose exec php bin/install.sh "<title>" <admin email> [business profile]
# Single site, or the network when ONCLOUD_NETWORK=1 (then set ONCLOUD_NETWORK_READY=1 and restart).
# Content, brand and catalogue arrive from OnCloud once the companion is paired (plan phases 2–4).
set -eu
title="${1:?site title}"
email="${2:?admin email}"
profile="${3:-general}"
wp="vendor/bin/wp --path=web/wp"
url="${WP_HOME:-http://${DOMAIN_CURRENT_SITE:-localhost}}"
password="$(php -r 'echo bin2hex(random_bytes(16));')"

if [ "${ONCLOUD_NETWORK:-0}" = "1" ]; then
  $wp core multisite-install --subdomains --url="$url" --title="$title" --admin_user=oncloud --admin_email="$email" --admin_password="$password" --skip-email --skip-config
else
  $wp core install --url="$url" --title="$title" --admin_user=oncloud --admin_email="$email" --admin_password="$password" --skip-email
fi

$wp theme activate oncloud
$wp plugin activate polylang
$wp eval-file bin/polylang-languages.php
$wp option update oncloud_style_variation "$profile"
$wp rewrite structure '/%postname%/'
$wp rewrite flush

if [ "${ONCLOUD_PLAN:-site}" != "site" ]; then
  $wp plugin activate woocommerce
fi

echo "Installed: $url (plan ${ONCLOUD_PLAN:-site}, profile $profile). Break-glass admin: oncloud / $password"
echo "Store the password in the registry's vault now; it is not shown again."
