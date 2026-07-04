<?php
// Usage: php tools/strip_bom.php /path/to/project
// Scans recursively for .php files and removes UTF-8 BOM if present.

$start = $argv[1] ?? __DIR__ . '/..';

echo "Scanning for PHP files under: $start\n";

$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($start));
$count = 0;
foreach ($it as $file) {
    if (!$file->isFile()) continue;
    $path = $file->getPathname();
    if (pathinfo($path, PATHINFO_EXTENSION) !== 'php') continue;

    $contents = file_get_contents($path);
    if ($contents === false) continue;
    if (substr($contents, 0, 3) === "\xEF\xBB\xBF") {
        // Backup
        copy($path, $path . '.bak');
        $new = substr($contents, 3);
        file_put_contents($path, $new);
        echo "Stripped BOM: $path (backup at $path.bak)\n";
        $count++;
    }
}

echo "Done. Files changed: $count\n";
