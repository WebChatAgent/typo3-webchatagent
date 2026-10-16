<?php

declare(strict_types=1);

namespace WebChatAgent\WebChatAgent;

/**
 * Chat settings of one site plus the widget markup.
 *
 * Kept free of TYPO3 APIs (and PHP 7.2 compatible) so Tests/selfcheck.php runs with plain PHP.
 * Always uses the brand-neutral tag and script names: every widget bundle registers them, so the
 * same output works for the WebChatAgent platform and for white-label servers.
 */
final class WidgetSettings
{
    public const DEFAULT_BASE_URL = 'https://webchatagent.com';
    public const FLOATING_TAG = 'chat-widget';
    public const INLINE_TAG = 'chat-agent-inline';
    public const FLOATING_SCRIPT = '/widget/chat-widget.js';
    public const INLINE_SCRIPT = '/widget/chat-widget-inline.js';

    private const UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
    private const HEIGHT_PATTERN = '/^\d+(\.\d+)?(px|vh|svh|dvh|%|rem|em)$/';
    // Every chat element, including the platform tag names of snippets pasted by hand.
    private const TAG_PATTERN = '/<(chat-widget|web-chat-agent|chat-agent-inline|web-chat-agent-inline)(?=[\s>])/i';

    /** @var string Empty when the site has no valid chatbot ID */
    public $chatbotId;

    /** @var string */
    public $baseUrl;

    /** @var bool */
    public $passFrontendUser;

    private function __construct(string $chatbotId, string $baseUrl, bool $passFrontendUser)
    {
        $this->chatbotId = $chatbotId;
        $this->baseUrl = $baseUrl;
        $this->passFrontendUser = $passFrontendUser;
    }

    /**
     * @param array<string, mixed> $configuration Site configuration (config/sites/<site>/config.yaml)
     */
    public static function fromSiteConfiguration(array $configuration): self
    {
        return new self(
            self::chatbotId($configuration['webchatagent_chatbot_id'] ?? ''),
            self::baseUrl($configuration['webchatagent_base_url'] ?? ''),
            !empty($configuration['webchatagent_frontend_user'])
        );
    }

    /**
     * @param mixed $value
     */
    public static function chatbotId($value): string
    {
        $value = trim((string)$value);
        return preg_match(self::UUID_PATTERN, $value) === 1 ? strtolower($value) : '';
    }

    /**
     * @param mixed $value
     */
    public static function baseUrl($value): string
    {
        $value = rtrim(trim((string)$value), '/');
        $scheme = parse_url($value, PHP_URL_SCHEME);
        $isValid = in_array($scheme, ['https', 'http'], true) && (string)parse_url($value, PHP_URL_HOST) !== '';
        return $isValid ? $value : self::DEFAULT_BASE_URL;
    }

    /**
     * CSS length for the inline chat, e.g. "600px" or "80vh". Empty means automatic height.
     *
     * @param mixed $value
     */
    public static function height($value): string
    {
        $value = trim((string)$value);
        return preg_match(self::HEIGHT_PATTERN, $value) === 1 ? $value : '';
    }

    public static function inlineElement(string $chatbotId, string $height): string
    {
        $attributes = ' chatbot-id="' . self::escape($chatbotId) . '"';
        if ($height !== '') {
            $attributes .= ' container-height="' . self::escape($height) . '"';
        }
        return '<' . self::INLINE_TAG . $attributes . '></' . self::INLINE_TAG . '>';
    }

    /**
     * Adds the floating widget and the inline chat script before </body> and hands the context data to every
     * chat element. Leaves the document untouched where nothing applies.
     *
     * @param callable $contextData Returns the context JSON or ''; only called when the page has a chat element
     */
    public function injectInto(string $html, bool $showFloating, callable $contextData): string
    {
        preg_match_all(self::TAG_PATTERN, $html, $matches);
        $tags = array_map('strtolower', $matches[1]);
        $hasFloating = array_intersect($tags, ['chat-widget', 'web-chat-agent']) !== [];
        $hasInline = array_intersect($tags, ['chat-agent-inline', 'web-chat-agent-inline']) !== [];

        $markup = '';
        if ($showFloating && $this->chatbotId !== '' && !$hasFloating) {
            $markup .= $this->scriptTag(self::FLOATING_SCRIPT)
                . '<' . self::FLOATING_TAG . ' chatbot-id="' . self::escape($this->chatbotId) . '"></' . self::FLOATING_TAG . '>';
        }
        if ($hasInline && strpos($html, self::INLINE_SCRIPT) === false) {
            $markup .= $this->scriptTag(self::INLINE_SCRIPT);
        }
        $bodyEnd = $markup !== '' ? strripos($html, '</body>') : false;
        if ($bodyEnd !== false) {
            $html = substr_replace($html, $markup, $bodyEnd, 0);
        }

        $context = $tags !== [] || $bodyEnd !== false ? (string)$contextData() : '';
        if ($context === '') {
            return $html;
        }
        $attribute = ' context-data="' . self::escape($context) . '"';
        // Callback instead of a replacement string: "$" and "\" in the data must not act as backreferences.
        return (string)preg_replace_callback(self::TAG_PATTERN, static function (array $match) use ($attribute): string {
            return $match[0] . $attribute;
        }, $html);
    }

    /**
     * scheme://host[:port] of the chat server, for the Content-Security-Policy.
     */
    public function origin(): string
    {
        $port = parse_url($this->baseUrl, PHP_URL_PORT);
        return parse_url($this->baseUrl, PHP_URL_SCHEME) . '://' . parse_url($this->baseUrl, PHP_URL_HOST) . ($port ? ':' . $port : '');
    }

    private function scriptTag(string $path): string
    {
        return '<script src="' . self::escape($this->baseUrl . $path) . '" async></script>';
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
