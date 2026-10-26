<?php
/**
 * MAY THIS HOST HAVE A CERTIFICATE? (deploy/Caddyfile, 26 Oct 2026). Caddy issues HTTPS certificates on demand, the
 * first time a host is asked for, and asks here first, from inside the server: 200 means yes, anything else no. So a
 * stranger pointing a domain at the server cannot make it request certificates for names that are not ours.
 *
 *   one site (a Private Store)   only the host of WP_HOME
 *   the shared network           its main domain, and every site of the network that is live (not deleted, archived
 *                                or marked spam) — read from the network's own `blogs` table
 *
 * WordPress is not loaded: one indexed query, so a burst of new hosts costs almost nothing. The public cannot reach
 * this file: Caddy answers 404 for it on every site, and asks for it over the server's internal network only.
 */

$domain = strtolower(trim((string) ($_GET['domain'] ?? '')));
$env = static fn (string $key, string $default = ''): string => ($v = getenv($key)) === false || $v === '' ? $default : $v;
$answer = static function (int $status): never {
    http_response_code($status);
    header('Content-Type: text/plain');
    echo $status === 200 ? 'yes' : 'no';
    exit;
};

if ($domain === '' || strlen($domain) > 253 || preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)*$/', $domain) !== 1) {
    $answer(400);
}

if ($env('ONCLOUD_NETWORK') !== '1') {
    $answer($domain === strtolower((string) parse_url($env('WP_HOME'), PHP_URL_HOST)) ? 200 : 403);
}

$main = strtolower($env('DOMAIN_CURRENT_SITE'));
if ($main !== '' && $domain === $main) {
    $answer(200);
}

mysqli_report(MYSQLI_REPORT_OFF);
$db = @mysqli_connect($env('DB_HOST', 'db'), $env('DB_USER'), $env('DB_PASSWORD'), $env('DB_NAME'));
if (! $db instanceof mysqli) {
    $answer(503);
}

$table = preg_replace('/[^A-Za-z0-9_]/', '', $env('DB_PREFIX', 'wp_')).'blogs';
$query = $db->prepare("SELECT 1 FROM `{$table}` WHERE domain = ? AND deleted = 0 AND archived = '0' AND spam = 0 LIMIT 1");
if ($query === false) {
    $answer(503);
}
$query->bind_param('s', $domain);
$query->execute();
$query->store_result();

$answer($query->num_rows === 1 ? 200 : 403);
