<?php

defined('TYPO3') || defined('TYPO3_MODE') || die();

(static function (): void {
    $ll = 'LLL:EXT:webchatagent/Resources/Private/Language/locallang_db.xlf:';
    $typo3Major = (new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion();

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tt_content', [
        'tx_webchatagent_chatbot_id' => [
            'exclude' => true,
            'label' => $ll . 'inline.chatbotId',
            'description' => $ll . 'inline.chatbotId.description',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'max' => 36,
                'eval' => 'trim',
                'placeholder' => '3f2b9c1e-8a7d-4e5f-9b0c-1d2e3f4a5b6c',
            ],
        ],
        'tx_webchatagent_height' => [
            'exclude' => true,
            'label' => $ll . 'inline.height',
            'description' => $ll . 'inline.height.description',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'max' => 16,
                'eval' => 'trim',
                'placeholder' => '600px',
            ],
        ],
    ]);

    // Associative item format from TYPO3 12 on, numeric before.
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem('tt_content', 'CType', $typo3Major >= 12
        ? [
            'label' => $ll . 'inline.title',
            'description' => $ll . 'inline.description',
            'value' => 'webchatagent_inline',
            'icon' => 'webchatagent-inline',
            'group' => 'special',
        ]
        : [$ll . 'inline.title', 'webchatagent_inline', 'webchatagent-inline', 'special']);

    $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['webchatagent_inline'] = 'webchatagent-inline';
    $GLOBALS['TCA']['tt_content']['types']['webchatagent_inline'] = [
        'showitem' => '--div--;' . $ll . 'tab.general, --palette--;;general, header,'
            . ' tx_webchatagent_chatbot_id, tx_webchatagent_height,'
            . ' --div--;' . $ll . 'tab.language, --palette--;;language,'
            . ' --div--;' . $ll . 'tab.access, --palette--;;hidden, --palette--;;access',
        'columnsOverrides' => [
            'header' => ['label' => $ll . 'inline.header'],
        ],
    ];
})();
