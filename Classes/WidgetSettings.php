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
    private const TAG_START = '<(chat-widget|web-chat-agent|chat-agent-inline|web-chat-agent-inline)(?=[\s>])';
    private const TAG_PATTERN = '/' . self::TAG_START . '/i';
    // Opening tag up to its ">" (quoted values may contain ">"); group 2 holds the attributes. Possessive, so a
    // huge tag cannot exhaust the PCRE stack.
    private const OPENING_TAG_PATTERN = '/' . self::TAG_START . '((?:[^>"\']++|"[^"]*+"|\'[^\']*+\')*+)/i';
    // One well-formed attribute. A tag made of anything else gets no context data: for malformed markup a regex
    // cannot tell which chatbot-id the browser will use.
    private const ATTRIBUTE = '\s+([a-zA-Z_:][-a-zA-Z0-9_:.]*)(?:\s*=\s*(?|"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]+)))?';
    private const SIGNATURE_ATTRIBUTE = 'data-webchatagent-signature';

    /** @var string Empty when the site has no valid chatbot ID */
    public $chatbotId;

    /** @var string */
    public $baseUrl;

    /** @var bool */
    public $passFrontendUser;

    /** @var string Signs the inline elements this extension renders; empty signs nothing */
    private $secret;

    private function __construct(string $chatbotId, string $baseUrl, bool $passFrontendUser, string $secret)
    {
        $this->chatbotId = $chatbotId;
        $this->baseUrl = $baseUrl;
        $this->passFrontendUser = $passFrontendUser;
        $this->secret = $secret;
    }

    /**
     * The inline-element signature uses the TYPO3 encryption key, read from the globals (empty in plain PHP).
     *
     * @param array<string, mixed> $configuration Site configuration (config/sites/<site>/config.yaml)
     */
    public static function fromSiteConfiguration(array $configuration): self
    {
        return new self(
            self::chatbotId($configuration['webchatagent_chatbot_id'] ?? ''),
            self::baseUrl($configuration['webchatagent_base_url'] ?? ''),
            !empty($configuration['webchatagent_frontend_user']),
            (string)($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] ?? '')
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

    /**
     * The signature marks the element as rendered by this extension, so it receives the context data even when
     * its chatbot ID differs from the site's. It is deterministic and survives the page cache.
     */
    public function inlineElement(string $chatbotId, string $height): string
    {
        $attributes = ' chatbot-id="' . self::escape($chatbotId) . '"';
        $signature = $this->signature($chatbotId);
        if ($signature !== '') {
            $attributes .= ' ' . self::SIGNATURE_ATTRIBUTE . '="' . $signature . '"';
        }
        if ($height !== '') {
            $attributes .= ' container-height="' . self::escape($height) . '"';
        }
        return '<' . self::INLINE_TAG . $attributes . '></' . self::INLINE_TAG . '>';
    }

    /**
     * Adds the floating widget and the inline chat script before </body> and hands the context data to the chat
     * elements of this site: the site's chatbot ID or a signed inline element. Snippets of other chatbots pasted
     * into the content get no context data, it may hold the visitor's name and email address.
     * Leaves the document untouched where nothing applies.
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
        $result = preg_replace_callback(self::OPENING_TAG_PATTERN, function (array $match) use ($attribute): string {
            return $this->isOwnElement($match[2]) ? '<' . $match[1] . $attribute . $match[2] : $match[0];
        }, $html);
        // A PCRE failure must never blank the page: deliver it without context data instead.
        return $result ?? $html;
    }

    /**
     * scheme://host[:port] of the chat server, for the Content-Security-Policy.
     */
    public function origin(): string
    {
        $port = parse_url($this->baseUrl, PHP_URL_PORT);
        return parse_url($this->baseUrl, PHP_URL_SCHEME) . '://' . parse_url($this->baseUrl, PHP_URL_HOST) . ($port ? ':' . $port : '');
    }

    private function isOwnElement(string $attributeSource): bool
    {
        $attributes = self::attributes($attributeSource);
        $chatbotId = self::chatbotId($attributes['chatbot-id'] ?? '');
        if ($chatbotId === '') {
            return false;
        }
        if ($chatbotId === $this->chatbotId) {
            return true;
        }
        $signature = $this->signature($chatbotId);
        return $signature !== '' && hash_equals($signature, $attributes[self::SIGNATURE_ATTRIBUTE] ?? '');
    }

    private function signature(string $chatbotId): string
    {
        // ponytail: a changed encryptionKey leaves cached inline elements unsigned until the page cache is flushed.
        return $this->secret === '' ? '' : hash_hmac('sha256', 'webchatagent-inline:' . $chatbotId, $this->secret);
    }

    /**
     * Attributes of a well-formed opening tag (the part after the tag name), read like a browser does: names are
     * case-insensitive, the first occurrence wins. Empty for malformed tags.
     *
     * @return array<string, string>
     */
    private static function attributes(string $source): array
    {
        if (preg_match('/^(?:' . self::ATTRIBUTE . ')*\s*\/?$/', $source) !== 1) {
            return [];
        }
        preg_match_all('/' . self::ATTRIBUTE . '/', $source, $matches, PREG_SET_ORDER);
        $attributes = [];
        foreach ($matches as $m) {
            $name = strtolower($m[1]);
            if (!isset($attributes[$name])) {
                $attributes[$name] = html_entity_decode($m[2] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }
        return $attributes;
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
