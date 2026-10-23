<?php
/**
 * THE DISTRIBUTION'S RULES, CHECKED (ADR 0052) — run in CI before every image and inside the build:
 *
 *   php bin/check.php              the repository
 *   php bin/check.php --installed  also what Composer installed (inside the image build)
 *
 *   PINS        every Composer requirement is an exact version, or a branch pinned to a 40-character commit
 *   ALLOWLIST   every plugin package required has an allowlist entry, and every entry's package is required
 *   HARDENING   the configuration still sets the file-edit, file-mod and auto-update switches
 *   THEME       theme.json (v3) parses; one style variation per business profile, each with the base palette's slugs;
 *               every font file it names exists, with its licence
 *   VERSION     VERSION is semantic and CHANGELOG.md has its entry
 *   INSTALLED   (--installed) WordPress core, the companion as a must-use plugin, and only allowed plugins in web/app/plugins
 */

$root = dirname(__DIR__);
$errors = [];
$fail = static function (string $message) use (&$errors): void {
    $errors[] = $message;
};
$json = static function (string $path) use ($fail): ?array {
    $data = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
    if (! is_array($data)) {
        $fail("Not valid JSON: {$path}");

        return null;
    }

    return $data;
};

// The business profiles the theme carries a look for (oncloud-vanilla resources/marketing/oncloud-site/business-types.php).
$profiles = ['general', 'government', 'b2b_services', 'bakery', 'barber', 'beauty_salon', 'grocery_shop', 'marketing_agency'];

$composer = $json($root.'/composer.json') ?? [];
foreach ((array) ($composer['require'] ?? []) as $package => $constraint) {
    if ($package === 'php') {
        continue;
    }
    if (! preg_match('/^v?\d+\.\d+(\.\d+)?$/', $constraint) && ! preg_match('/^dev-[a-z0-9._\-\/]+#[0-9a-f]{40}$/', $constraint)) {
        $fail("composer.json: {$package} must be pinned to an exact version or commit, not \"{$constraint}\".");
    }
}

$allowlist = require $root.'/config/allowlist.php';
foreach ($allowlist as $slug => $entry) {
    if (! isset($composer['require'][$entry['package'] ?? ''])) {
        $fail("allowlist: {$slug} names {$entry['package']}, which composer.json does not require.");
    }
    if (! in_array($entry['required'] ?? null, ['always', 'shop', 'never'], true)) {
        $fail("allowlist: {$slug} has no valid `required` (always, shop or never).");
    }
    if (trim((string) ($entry['why'] ?? '')) === '') {
        $fail("allowlist: {$slug} has no `why`.");
    }
}
$allowedPackages = array_column($allowlist, 'package');
foreach (array_keys((array) ($composer['require'] ?? [])) as $package) {
    if (str_starts_with($package, 'wpackagist-plugin/') && ! in_array($package, $allowedPackages, true)) {
        $fail("composer.json requires {$package}, which config/allowlist.php does not allow.");
    }
}

$config = (string) file_get_contents($root.'/config/application.php');
foreach (["define('DISALLOW_FILE_EDIT', true);", "define('DISALLOW_FILE_MODS', \$locked);", "define('AUTOMATIC_UPDATER_DISABLED', true);", "define('WP_AUTO_UPDATE_CORE', false);"] as $line) {
    if (! str_contains($config, $line)) {
        $fail("config/application.php no longer has: {$line}");
    }
}

$themeDir = $root.'/web/app/themes/oncloud';
$theme = $json($themeDir.'/theme.json') ?? [];
if (($theme['version'] ?? null) !== 3) {
    $fail('theme.json must be version 3.');
}
$slugs = array_column((array) ($theme['settings']['color']['palette'] ?? []), 'slug');
sort($slugs);
$found = array_map(static fn (string $f): string => basename($f, '.json'), glob($themeDir.'/styles/*.json') ?: []);
sort($found);
$expected = $profiles;
sort($expected);
if ($found !== $expected) {
    $fail('theme styles/ must hold exactly one variation per business profile: '.implode(', ', $expected).'.');
}
foreach ($found as $profile) {
    $variation = $json("{$themeDir}/styles/{$profile}.json") ?? [];
    $own = array_column((array) ($variation['settings']['color']['palette'] ?? []), 'slug');
    sort($own);
    if ($own !== $slugs) {
        $fail("styles/{$profile}.json must define the palette slugs ".implode(', ', $slugs).'.');
    }
    if (trim((string) ($variation['title'] ?? '')) === '') {
        $fail("styles/{$profile}.json has no title.");
    }
}
foreach ((array) ($theme['settings']['typography']['fontFamilies'] ?? []) as $family) {
    foreach ((array) ($family['fontFace'] ?? []) as $face) {
        foreach ((array) ($face['src'] ?? []) as $src) {
            $file = $themeDir.'/'.preg_replace('#^file:\./#', '', $src);
            if (! is_file($file)) {
                $fail("theme.json names a font that is missing: {$src}");
            }
        }
    }
}
foreach (['OFL-inter.txt', 'OFL-plex-arabic.txt'] as $licence) {
    if (! is_file($themeDir.'/assets/fonts/'.$licence)) {
        $fail("The font licence {$licence} must ship with the fonts.");
    }
}

$version = trim((string) @file_get_contents($root.'/VERSION'));
if (! preg_match('/^\d+\.\d+\.\d+$/', $version)) {
    $fail('VERSION must be a semantic version (x.y.z).');
} elseif (! str_contains((string) @file_get_contents($root.'/CHANGELOG.md'), "## {$version}")) {
    $fail("CHANGELOG.md has no \"## {$version}\" entry.");
}

if (in_array('--installed', $argv, true)) {
    if (! is_file($root.'/web/wp/wp-settings.php')) {
        $fail('WordPress core is not installed in web/wp.');
    }
    if (! is_file($root.'/web/app/mu-plugins/oncloud/oncloud.php')) {
        $fail('The companion is not installed as a must-use plugin in web/app/mu-plugins/oncloud.');
    }
    foreach (glob($root.'/web/app/plugins/*', GLOB_ONLYDIR) ?: [] as $dir) {
        if (! isset($allowlist[basename($dir)])) {
            $fail('web/app/plugins holds '.basename($dir).', which config/allowlist.php does not allow.');
        }
    }
}

if (! is_file($root.'/composer.lock')) {
    fwrite(STDERR, "Warning: composer.lock is missing; the build resolves the pins itself. Commit the lock from the CI artifact.\n");
}

if ($errors !== []) {
    fwrite(STDERR, "OnCloud WordPress check failed:\n - ".implode("\n - ", $errors)."\n");
    exit(1);
}
echo "OnCloud WordPress {$version}: check passed".(in_array('--installed', $argv, true) ? ' (installed tree included)' : '').".\n";
