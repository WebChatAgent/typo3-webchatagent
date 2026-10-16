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

$inlinePage = '<html><body>' . WidgetSettings::inlineElement($id, '80vh') . '</body></html>';
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

$called = false;
WidgetSettings::fromSiteConfiguration([])->injectInto($page, true, function () use (&$called): string {
    $called = true;
    return '{}';
});
check(!$called, 'context data is only computed for pages with a chat element');
check(WidgetSettings::fromSiteConfiguration(['webchatagent_base_url' => 'http://localhost:3000/'])->origin() === 'http://localhost:3000', 'origin keeps the port');

echo 'selfcheck ok' . PHP_EOL;
