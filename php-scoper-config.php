<?php

declare(strict_types=1);

return [
    // Prefix applied to all vendor code
    'prefix' => 'Accredible\\Vendor',

    // Whitelist: Don't prefix these namespaces (WordPress core, global functions)
    'whitelist' => [
        // WordPress core classes
        'WP_*',
    ],

    // Only scope the vendor directory, exclude plugin files
    'finders' => [
        [
            'in' => 'vendor',
            'name' => '*.php',
            'exclude' => [
                'vendor/*/tests',
                'vendor/*/Tests',
                'vendor/*/test',
                'vendor/*/Test',
            ],
        ],
    ],

    // Whitelist global functions and classes that shouldn't be prefixed
    'whitelist-global-functions' => true,
    'whitelist-global-classes' => true,

    // Exclude files from being scoped (plugin files should not be scoped)
    'exclude-files' => [],
];

