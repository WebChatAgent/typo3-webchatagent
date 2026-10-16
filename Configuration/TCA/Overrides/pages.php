<?php

defined('TYPO3') || defined('TYPO3_MODE') || die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', [
    'tx_webchatagent_hide' => [
        'exclude' => true,
        'l10n_mode' => 'exclude',
        'label' => 'LLL:EXT:webchatagent/Resources/Private/Language/locallang_db.xlf:page.hide',
        'description' => 'LLL:EXT:webchatagent/Resources/Private/Language/locallang_db.xlf:page.hide.description',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'default' => 0,
        ],
    ],
]);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    '--div--;LLL:EXT:webchatagent/Resources/Private/Language/locallang_db.xlf:tab.chat, tx_webchatagent_hide'
);
