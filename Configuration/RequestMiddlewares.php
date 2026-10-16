<?php

return [
    'frontend' => [
        'webchatagent/widget-injector' => [
            'target' => \WebChatAgent\WebChatAgent\Middleware\WidgetInjector::class,
            // Innermost position: Content-Length, CSP and static file caches see the page with the chat.
            // Identifiers unknown to a TYPO3 version are ignored.
            'after' => [
                'typo3/cms-frontend/page-resolver',
                'typo3/cms-frontend/content-length-headers',
                'typo3/cms-frontend/csp-headers',
                'staticfilecache/generate',
            ],
        ],
    ],
];
