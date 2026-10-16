<?php

declare(strict_types=1);

namespace WebChatAgent\WebChatAgent\Frontend;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Attribute\AsAllowedCallable;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use WebChatAgent\WebChatAgent\WidgetSettings;

/**
 * Renders the "Inline chat" content element. The script is added by the WidgetInjector middleware.
 */
final class InlineChatRenderer
{
    /** @var ContentObjectRenderer|null Set as property by TYPO3 10/11, via setter from 12 on */
    public $cObj;

    public function setContentObjectRenderer(ContentObjectRenderer $cObj): void
    {
        $this->cObj = $cObj;
    }

    /**
     * @param array<string, mixed> $conf
     */
    #[AsAllowedCallable]
    public function render(string $content, array $conf, ?ServerRequestInterface $request = null): string
    {
        $record = $this->cObj !== null ? $this->cObj->data : [];
        $request = $request ?? ($GLOBALS['TYPO3_REQUEST'] ?? null);
        $site = $request instanceof ServerRequestInterface ? $request->getAttribute('site') : null;
        $siteConfiguration = $site instanceof Site ? $site->getConfiguration() : [];

        $chatbotId = WidgetSettings::chatbotId($record['tx_webchatagent_chatbot_id'] ?? '')
            ?: WidgetSettings::fromSiteConfiguration($siteConfiguration)->chatbotId;
        if ($chatbotId === '') {
            return '';
        }
        return WidgetSettings::inlineElement($chatbotId, WidgetSettings::height($record['tx_webchatagent_height'] ?? ''));
    }
}
