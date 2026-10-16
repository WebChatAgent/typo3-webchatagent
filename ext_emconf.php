<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'WebChatAgent – AI Chatbot & Live Chat',
    'description' => 'AI chatbot and live chat for TYPO3 10.4 to 14. The bot answers visitors from your own website content, collects contact requests and hands tricky chats to your team. One chatbot ID per site, no TypoScript. GDPR: servers in Germany, data processing agreement under Art. 28 at sign-up, new chatbots use an EU-hosted AI model by default. More: https://webchatagent.com/typo3-extension',
    'category' => 'fe',
    'author' => 'WebChatAgent',
    'author_company' => 'WebChatAgent',
    'state' => 'stable',
    'version' => '1.0.5',
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
