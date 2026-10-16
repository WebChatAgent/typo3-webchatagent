<?php

declare(strict_types=1);

// Plain-PHP self-check (no TYPO3 needed): php Tests/selfcheck.php

require __DIR__ . '/../Classes/WidgetSettings.php';

use WebChatAgent\WebChatAgent\WidgetSettings;

function check(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
        exit(1);
    }
}

$id = '3f2b9c1e-8a7d-4e5f-9b0c-1d2e3f4a5b6c';
$otherId = '9a8b7c6d-5e4f-4a3b-8c2d-1e0f9a8b7c6d';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'selfcheck-key';
$noContext = function (): string {
    return '';
};
$page = '<html><body><p>Text</p></body></html>';

check(WidgetSettings::chatbotId(' ' . strtoupper($id) . ' ') === $id, 'chatbot ID is trimmed and lower-cased');
check(WidgetSettings::chatbotId('not-a-uuid') === '', 'invalid chatbot ID is rejected');
check(WidgetSettings::baseUrl('') === WidgetSettings::DEFAULT_BASE_URL, 'empty base URL falls back to default');
check(WidgetSettings::baseUrl('https://chat.example.com/ ') === 'https://chat.example.com', 'base URL loses trailing slash');
check(WidgetSettings::baseUrl('javascript:alert(1)') === WidgetSettings::DEFAULT_BASE_URL, 'non-http base URL is rejected');
check(WidgetSettings::height('600px') === '600px' && WidgetSettings::height('600px;color:red') === '', 'height is validated');

$settings = WidgetSettings::fromSiteConfiguration(['webchatagent_chatbot_id' => $id]);
$html = $settings->injectInto($page, true, $noContext);
check(strpos($html, '<script src="https://webchatagent.com/widget/chat-widget.js" async></script><chat-widget chatbot-id="' . $id . '"></chat-widget></body>') !== false, 'floating widget is placed before </body>');
check($settings->injectInto($page, false, $noContext) === $page, 'hidden page stays untouched');
check(WidgetSettings::fromSiteConfiguration([])->injectInto($page, true, $noContext) === $page, 'site without chatbot ID stays untouched');
check(substr_count($settings->injectInto($html, true, $noContext), '<chat-widget ') === 1, 'widget is never added twice');

$inlinePage = '<html><body>' . $settings->inlineElement($id, '80vh') . '</body></html>';
$html = WidgetSettings::fromSiteConfiguration([])->injectInto($inlinePage, true, $noContext);
check(strpos($html, 'chat-widget-inline.js') !== false && strpos($html, '<chat-widget ') === false, 'inline chat gets its script without a site chatbot ID');
check(strpos($html, 'container-height="80vh"') !== false, 'inline height is rendered');

$context = '{"Name":"A \"B\" $1 <x>"}';
$html = $settings->injectInto($inlinePage, true, function () use ($context): string {
    return $context;
});
$attribute = 'context-data="' . htmlspecialchars($context, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"';
check(substr_count($html, $attribute) === 2, 'context data reaches floating and inline element, escaped');
check(strpos($html, '<x>') === false, 'context data cannot break out of the attribute');

$hasContext = function (string $body) use ($settings): bool {
    $html = $settings->injectInto('<html><body>' . $body . '</body></html>', false, function (): string {
        return '{"Email":"a@example.com"}';
    });
    return strpos($html, 'context-data=') !== false;
};
check($hasContext('<web-chat-agent CHATBOT-ID=\'' . strtoupper($id) . '\'></web-chat-agent>'), 'pasted snippet of the site chatbot gets context data');
check($hasContext('<chat-agent-inline title="a>b" chatbot-id="' . $id . '"></chat-agent-inline>'), '">" in a quoted value does not end the tag');
check($hasContext($settings->inlineElement($otherId, '')), 'signed inline element of another chatbot gets context data');
check(!$hasContext('<chat-widget chatbot-id="' . $otherId . '"></chat-widget>'), 'pasted snippet of another chatbot gets no context data');
$forged = str_replace('data-webchatagent-signature="', 'data-webchatagent-signature="0', $settings->inlineElement($otherId, ''));
check(!$hasContext($forged), 'forged signature gets no context data');
check(!$hasContext('<chat-widget data-x=" chatbot-id=' . $id . '" chatbot-id="' . $otherId . '"></chat-widget>'), 'chatbot-id inside another value is ignored');
check(!$hasContext('<chat-widget "chatbot-id=' . $id . '" chatbot-id="' . $otherId . '"></chat-widget>'), 'malformed tag gets no context data');
check(!$hasContext('<chat-widget chatbot-id="' . $otherId . '" chatbot-id="' . $id . '"></chat-widget>'), 'first chatbot-id wins like in the browser');
$hugeTag = '<p>Text</p><chat-widget chatbot-id="' . $id . '" data-x=' . str_repeat('a', 100000) . '></chat-widget>';
check(strpos($settings->injectInto('<html><body>' . $hugeTag . '</body></html>', false, function (): string {
    return '{"Email":"a@example.com"}';
}), '<p>Text</p>') !== false, 'a huge chat tag never blanks the page');
$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '';
check(strpos(WidgetSettings::fromSiteConfiguration([])->inlineElement($otherId, ''), 'signature') === false, 'no encryption key, no signature');

$called = false;
WidgetSettings::fromSiteConfiguration([])->injectInto($page, true, function () use (&$called): string {
    $called = true;
    return '{}';
});
check(!$called, 'context data is only computed for pages with a chat element');
check(WidgetSettings::fromSiteConfiguration(['webchatagent_base_url' => 'http://localhost:3000/'])->origin() === 'http://localhost:3000', 'origin keeps the port');

echo 'selfcheck ok' . PHP_EOL;
