<?php

return [
    'directory_list' => [
        'src',
        'vendor/container-interop/container-interop',
        'vendor/emonkak/http-exception',
        'vendor/emonkak/router',
        'vendor/http-interop/http-middleware',
        'vendor/psr/http-message',
        'vendor/psr/log',
    ],

    'exclude_analysis_directory_list' => [
        'vendor/',
    ],

    'analyze_signature_compatibility' => false,
];
