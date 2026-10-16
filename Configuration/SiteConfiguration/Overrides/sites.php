<?php

(static function (): void {
    $ll = 'LLL:EXT:webchatagent/Resources/Private/Language/locallang_db.xlf:';

    // Logo and links to WebChatAgent, rendered by Classes/Form/ChatInfoElement (nothing is stored)
    $GLOBALS['SiteConfiguration']['site']['columns']['webchatagent_info'] = [
        'label' => '',
        'config' => [
            'type' => 'user',
            'renderType' => 'webchatagentInfo',
        ],
    ];
    $GLOBALS['SiteConfiguration']['site']['columns']['webchatagent_chatbot_id'] = [
        'label' => $ll . 'site.chatbotId',
        'description' => $ll . 'site.chatbotId.description',
        'config' => [
            'type' => 'input',
            'eval' => 'trim',
            'max' => 36,
            'placeholder' => '3f2b9c1e-8a7d-4e5f-9b0c-1d2e3f4a5b6c',
        ],
    ];
    $GLOBALS['SiteConfiguration']['site']['columns']['webchatagent_frontend_user'] = [
        'label' => $ll . 'site.frontendUser',
        'description' => $ll . 'site.frontendUser.description',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'default' => 0,
        ],
    ];
    $GLOBALS['SiteConfiguration']['site']['columns']['webchatagent_base_url'] = [
        'label' => $ll . 'site.baseUrl',
        'description' => $ll . 'site.baseUrl.description',
        'config' => [
            'type' => 'input',
            'eval' => 'trim',
            'placeholder' => 'https://webchatagent.com',
        ],
    ];

    $GLOBALS['SiteConfiguration']['site']['types']['0']['showitem'] .= ',--div--;' . $ll . 'tab.chat,'
        . 'webchatagent_info, webchatagent_chatbot_id, webchatagent_frontend_user, webchatagent_base_url';
})();
