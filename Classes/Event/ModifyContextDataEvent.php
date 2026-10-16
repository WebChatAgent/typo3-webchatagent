<?php

declare(strict_types=1);

namespace WebChatAgent\WebChatAgent\Event;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Lets integrators pass own data to the chat, e.g. a customer number:
 * $event->setContextData($event->getContextData() + ['CustomerNumber' => '4711']);
 *
 * Values must be strings, numbers or booleans. The widget keeps at most 20 keys and 500 characters per value.
 */
final class ModifyContextDataEvent
{
    /** @var ServerRequestInterface */
    private $request;

    /** @var array<string, string|int|float|bool> */
    private $contextData;

    /**
     * @param array<string, string|int|float|bool> $contextData
     */
    public function __construct(ServerRequestInterface $request, array $contextData)
    {
        $this->request = $request;
        $this->contextData = $contextData;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * @return array<string, string|int|float|bool>
     */
    public function getContextData(): array
    {
        return $this->contextData;
    }

    /**
     * @param array<string, string|int|float|bool> $contextData
     */
    public function setContextData(array $contextData): void
    {
        $this->contextData = $contextData;
    }
}
