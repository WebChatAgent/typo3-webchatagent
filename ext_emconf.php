<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'WebChatAgent – AI Chatbot & Live Chat',
    'description' => 'AI chatbot and live chat for TYPO3: answers visitors from your website content, captures leads and hands chats to your team. Floating chat button, inline chat content element, per-site configuration.',
    'category' => 'fe',
    'author' => 'WebChatAgent',
    'author_company' => 'WebChatAgent',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.0-14.4.99',
            'php' => '7.2.0-8.5.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'psr-4' => [
            'WebChatAgent\\WebChatAgent\\' => 'Classes/',
        ],
    ],
];
