<?php
/**
 * A SITE'S .env FILE (decision 10, 27 Oct 2026). A Forge server keeps each site's settings in `.env` at the site's
 * root (Forge → the site → Environment). Docker and CI pass real environment variables instead, and those always win:
 * a key already in the environment is never read from the file.
 *
 *   KEY=value          one per line; a value may be wrapped in "double" or 'single' quotes
 *   # a comment        ignored, as are blank lines and anything that is not KEY=value
 *
 * No variable expansion, no `export`: the file holds plain values (database password, keys and salts, the plan).
 *
 * @return list<string> the keys it set
 */
function oncloud_load_dotenv(string $file): array
{
    if (! is_readable($file)) {
        return [];
    }

    $set = [];
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        if (preg_match('/^\s*([A-Z][A-Z0-9_]*)\s*=\s*(.*?)\s*$/', $line, $m) !== 1) {
            continue;
        }
        [, $key, $value] = $m;
        if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && substr($value, -1) === $value[0]) {
            $value = substr($value, 1, -1);
        }
        if (getenv($key) !== false) {
            continue;
        }
        putenv($key.'='.$value);
        $set[] = $key;
    }

    return $set;
}
