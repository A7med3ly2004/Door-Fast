<?php
$dirs = [
    'resources/views/delivery',
    'resources/views/reserve_delivery',
    'resources/views/callcenter',
    'resources/views/admin'
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) continue;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
            $content = file_get_contents($file->getPathname());
            // Match script blocks and globally replace `let`/`const` with `var` in the outermost scope of scripts.
            // A simple regex approach that replaces leading let/const on lines:
            $newContent = preg_replace('/^(let|const)\s+([a-zA-Z0-9_]+)\s*=/m', 'var $2 =', $content);
            $newContent = preg_replace('/^(let|const)\s+([a-zA-Z0-9_]+);/m', 'var $2;', $newContent);
            if ($content !== $newContent) {
                file_put_contents($file->getPathname(), $newContent);
                echo "Updated: " . $file->getPathname() . "\n";
            }
        }
    }
}
echo "Done.\n";
