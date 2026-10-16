<?php

defined('TYPO3') || defined('TYPO3_MODE') || die();

(static function (): void {
    $typo3Major = (new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion();

    // Global TypoScript: works with sys_template records and with site sets, no static include needed.
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup(
        "tt_content.webchatagent_inline = USER\n"
        . 'tt_content.webchatagent_inline.userFunc = '
        . \WebChatAgent\WebChatAgent\Frontend\InlineChatRenderer::class . "->render\n"
    );

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1792140000] = [
        'nodeName' => 'webchatagentInfo',
        'priority' => 40,
        'class' => \WebChatAgent\WebChatAgent\Form\ChatInfoElement::class,
    ];

    if ($typo3Major < 13) {
        // From TYPO3 13 on, rootline records contain all page fields.
        $GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] = trim(
            ($GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] ?? '') . ',tx_webchatagent_hide',
            ','
        );

        // From TYPO3 13 on, the new content element wizard reads the CType item from TCA.
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
            mod.wizards.newContentElement.wizardItems.special {
                elements.webchatagent_inline {
                    iconIdentifier = webchatagent-inline
                    title = LLL:EXT:webchatagent/Resources/Private/Language/locallang_db.xlf:inline.title
                    description = LLL:EXT:webchatagent/Resources/Private/Language/locallang_db.xlf:inline.description
                    tt_content_defValues.CType = webchatagent_inline
                }
                show := addToList(webchatagent_inline)
            }
        ');
    }

    if ($typo3Major < 11) {
        // Configuration/Icons.php is read from TYPO3 11 on.
        \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class)->registerIcon(
            'webchatagent-inline',
            \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            ['source' => 'EXT:webchatagent/Resources/Public/Icons/InlineChat.svg']
        );
    }
})();
