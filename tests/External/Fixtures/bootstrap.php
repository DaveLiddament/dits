<?php

declare(strict_types=1);

require_once __DIR__.'/../../../vendor/autoload.php';

spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'Fixtures\\Tests\\' => __DIR__.'/tests/',
        'Fixtures\\' => __DIR__.'/src/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        if (str_starts_with($class, $prefix)) {
            $relativeClass = substr($class, strlen($prefix));
            $file = $baseDir.str_replace('\\', '/', $relativeClass).'.php';
            if (file_exists($file)) {
                require_once $file;
            }

            return;
        }
    }
});
