<?php
/**
 * Autoloader simple pour charger automatiquement les classes
 */
spl_autoload_register(function ($class) {
    $directories = [
        __DIR__ . '/config/',
        __DIR__ . '/models/',
        __DIR__ . '/repository/',
        __DIR__ . '/services/',
        __DIR__ . '/dto/',
        __DIR__ . '/mappers/'
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

