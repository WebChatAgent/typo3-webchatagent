<?php

declare(strict_types=1);

namespace WebChatAgent\WebChatAgent\Middleware;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use WebChatAgent\WebChatAgent\Event\ModifyContextDataEvent;
use WebChatAgent\WebChatAgent\WidgetSettings;

/**
 * Adds the chat to every HTML page of a site with a configured chatbot ID.
 *
 * Runs on each request after rendering, so user-specific context data never ends up in the page cache.
 */
final class WidgetInjector implements MiddlewareInterface
{
    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var StreamFactoryInterface */
    private $streamFactory;

    public function __construct(EventDispatcherInterface $eventDispatcher, StreamFactoryInterface $streamFactory)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->streamFactory = $streamFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $site = $request->getAttribute('site');
        $contentType = $response->getHeaderLine('Content-Type');
        if (!$site instanceof Site || ($contentType !== '' && stripos($contentType, 'text/html') !== 0)) {
            return $response;
        }

        // The chat is an add-on: whatever goes wrong here, the page itself must still be delivered.
        try {
            return $this->withChat($request, $response, $site);
        } catch (\Throwable $e) {
            return $response;
        }
    }

    private function withChat(ServerRequestInterface $request, ResponseInterface $response, Site $site): ResponseInterface
    {
        $html = (string)$response->getBody();
        $settings = WidgetSettings::fromSiteConfiguration($site->getConfiguration());
        $result = $settings->injectInto(
            $html,
            $settings->chatbotId !== '' && !$this->isHiddenOnPage($request),
            function () use ($request, $settings): string {
                return $this->contextData($request, $settings);
            }
        );
        if ($result === $html) {
            return $response;
        }

        $response = $response->withBody($this->streamFactory->createStream($result));
        return $response->hasHeader('Content-Length')
            ? $response->withHeader('Content-Length', (string)strlen($result))
            : $response;
    }

    private function isHiddenOnPage(ServerRequestInterface $request): bool
    {
        $routing = $request->getAttribute('routing');
        if (!$routing instanceof PageArguments) {
            return false;
        }
        $rootline = GeneralUtility::makeInstance(RootlineUtility::class, $routing->getPageId())->get();
        return (bool)array_filter(array_column($rootline, 'tx_webchatagent_hide'));
    }

    private function contextData(ServerRequestInterface $request, WidgetSettings $settings): string
    {
        $contextData = [];
        $frontendUser = $request->getAttribute('frontend.user');
        if ($settings->passFrontendUser && $frontendUser instanceof FrontendUserAuthentication && is_array($frontendUser->user)) {
            $user = $frontendUser->user;
            $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: (string)($user['name'] ?? '');
            $contextData = array_filter([
                'Name' => $name,
                'Email' => (string)($user['email'] ?? ''),
                'Username' => (string)($user['username'] ?? ''),
            ], 'strlen');
        }

        $event = $this->eventDispatcher->dispatch(new ModifyContextDataEvent($request, $contextData));
        $contextData = $event->getContextData();
        return $contextData === [] ? '' : (string)json_encode($contextData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
