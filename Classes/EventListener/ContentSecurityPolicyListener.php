<?php

declare(strict_types=1);

namespace WebChatAgent\WebChatAgent\EventListener;

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Event\PolicyMutatedEvent;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceKeyword;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;
use TYPO3\CMS\Core\Site\Entity\Site;
use WebChatAgent\WebChatAgent\WidgetSettings;

/**
 * Allows the chat server in the frontend Content-Security-Policy (TYPO3 12+, only dispatched where CSP is active).
 *
 * Reads the server address per site, so white-label servers work without manual csp.yaml entries.
 * The widget renders <style> elements in its shadow DOM, hence 'unsafe-inline' for style-src.
 */
final class ContentSecurityPolicyListener
{
    public function __invoke(PolicyMutatedEvent $event): void
    {
        $site = $event->scope->site;
        if (!$event->scope->isFrontendSite() || !$site instanceof Site) {
            return;
        }

        $origin = WidgetSettings::fromSiteConfiguration($site->getConfiguration())->origin();
        $webSocketOrigin = preg_replace('/^http/', 'ws', $origin);

        $event->setCurrentPolicy(
            $event->getCurrentPolicy()
                ->extend(Directive::ScriptSrc, new UriValue($origin))
                ->extend(Directive::ConnectSrc, new UriValue($origin), new UriValue($webSocketOrigin))
                ->extend(Directive::ImgSrc, new UriValue($origin))
                ->extend(Directive::MediaSrc, new UriValue($origin))
                ->extend(Directive::StyleSrc, SourceKeyword::unsafeInline)
        );
    }
}
