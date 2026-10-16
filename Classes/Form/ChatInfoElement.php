<?php

declare(strict_types=1);

namespace WebChatAgent\WebChatAgent\Form;

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebChatAgent\WebChatAgent\WidgetSettings;

/**
 * Logo and links to WebChatAgent at the top of the site's "Chat" tab.
 *
 * Shown only while the default server is used: with a white-label server address the
 * tab stays brand-neutral. No own constructor, so it works with the node factories of
 * TYPO3 10/11 (constructor arguments) and 12+ (setData()).
 */
final class ChatInfoElement extends AbstractNode
{
    private const LL = 'LLL:EXT:webchatagent/Resources/Private/Language/locallang_db.xlf:info.';

    public function render(): array
    {
        $result = $this->initializeResultArray();
        $row = $this->data['databaseRow'] ?? [];
        if (WidgetSettings::baseUrl($row['webchatagent_base_url'] ?? '') !== WidgetSettings::DEFAULT_BASE_URL) {
            return $result;
        }

        $chatbotId = WidgetSettings::chatbotId($row['webchatagent_chatbot_id'] ?? '');
        $links = $chatbotId !== ''
            ? [WidgetSettings::DEFAULT_BASE_URL . '/dashboard/' . $chatbotId => 'openDashboard']
            : [WidgetSettings::DEFAULT_BASE_URL . '/register' => 'register'];
        $links['https://docs.webchatagent.com/integrations/typo3'] = 'guide';

        $buttons = '';
        foreach ($links as $url => $key) {
            $buttons .= '<a class="btn btn-default btn-sm" href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener">'
                . htmlspecialchars($this->label($key)) . '</a> ';
        }
        $logo = (string)file_get_contents(GeneralUtility::getFileAbsFileName('EXT:webchatagent/Resources/Public/Icons/Extension.svg'));

        $result['html'] = '<div class="callout callout-info" style="display:flex;gap:16px;align-items:flex-start">'
            . '<img src="data:image/svg+xml;base64,' . base64_encode($logo) . '" width="40" height="40" alt="">'
            . '<div><strong>' . htmlspecialchars($this->label('title')) . '</strong>'
            . '<p style="margin:4px 0 10px">' . htmlspecialchars($this->label($chatbotId !== '' ? 'connected' : 'notConnected')) . '</p>'
            . $buttons . '</div></div>';
        return $result;
    }

    private function label(string $key): string
    {
        return (string)$GLOBALS['LANG']->sL(self::LL . $key);
    }
}
