# WebChatAgent – AI Chatbot & Live Chat for TYPO3

Puts your [WebChatAgent](https://webchatagent.com) chatbot on a TYPO3 website. The bot answers visitors from your own website content, collects contact requests and hands a conversation to your team when it gets tricky. One field per site, no TypoScript to include, no template to touch.

Works with TYPO3 10.4, 11.5, 12.4, 13.4 and 14 (PHP 7.2 to 8.5) from a single release.

[Website](https://webchatagent.com/typo3-extension) · [Setup guide](https://docs.webchatagent.com/integrations/typo3) · [TYPO3 Extension Repository](https://extensions.typo3.org/extension/webchatagent) · [Pricing](https://webchatagent.com/pricing) (free plan available)

## What it does

- **Chat button on every page** of a site as soon as a chatbot ID is set. Multi-site installations get one ID per site.
- **Hide it where it doesn't belong**: a switch in the page properties hides the chat button on a page and all its subpages. Set it on the root page and the button is gone site-wide.
- **Inline chat content element** for contact or help pages. It uses the site's chatbot ID or its own.
- **Logged-in website users** (optional): name, email address and username go to the chat, so the bot doesn't ask for them again. This data is added per request and never ends up in the page cache.
- **Content Security Policy** (TYPO3 12+): the chat server is allowed automatically when CSP is active.
- **White-label ready**: point the extension at your own chat server address.

Settings like colours, texts, position and the bot's knowledge live in the WebChatAgent dashboard. Change them there and every page picks them up immediately.

## Privacy and GDPR

- Platform hosted in Germany (Frankfurt)
- Data processing agreement under Art. 28 GDPR, concluded at sign-up
- EU-based AI model for chats by default
- Your content is not used to train AI models through WebChatAgent
- Chats are deleted automatically after a retention period you set

What this covers and what you still check yourself: [AI chatbot and GDPR](https://webchatagent.com/ai-chatbot-gdpr).

## Installation

Composer:

```bash
composer require webchatagent/typo3-webchatagent
vendor/bin/typo3 extension:setup
```

Without Composer: install "webchatagent" in the Extension Manager (it creates the database fields).

TYPO3 10.4, 11.5 and 12.4 are end of life. Composer 2.9 and newer refuses to install anything into such projects while their core has open security advisories. If you run one of these versions without ELTS, set `composer config audit.block-insecure false` or install the extension through the Extension Manager.

## Setup

1. Create a free account at [webchatagent.com](https://webchatagent.com/register) and train your chatbot.
2. In the dashboard open your chatbot, choose **Chatbot actions** and then **Copy chatbot ID**.
3. In TYPO3 go to **Site Management > Sites**, edit your site and open the **Chat** tab. Paste the ID and save.

The chat button now shows on every page of that site. The **Chat** tab also links straight to your chatbot in the dashboard (hidden when a white-label server address is set).

To hide it on certain pages, open the page properties, tab **Chat**, and switch on "Hide the chat button on this page and all subpages".

To show the chat inside the content, add the content element **Inline chat** (group "Special elements"). Leave its chatbot ID empty to use the site's ID. The optional height takes values like `600px` or `80vh`.

Editors who are not admins need access to the fields `pages:tx_webchatagent_hide`, `tt_content:tx_webchatagent_chatbot_id`, `tt_content:tx_webchatagent_height` and the content type "Inline chat" in their backend group.

## Passing your own data to the chat

The chat can receive extra information, for example a customer number, so the bot can answer with that context. Register a listener for `WebChatAgent\WebChatAgent\Event\ModifyContextDataEvent`:

```php
use WebChatAgent\WebChatAgent\Event\ModifyContextDataEvent;

final class AddCustomerNumber
{
    public function __invoke(ModifyContextDataEvent $event): void
    {
        $user = $event->getRequest()->getAttribute('frontend.user');
        if ($user !== null && is_array($user->user)) {
            $event->setContextData($event->getContextData() + [
                'CustomerNumber' => (string)$user->user['tx_shop_customer_number'],
            ]);
        }
    }
}
```

```yaml
# Configuration/Services.yaml of your site package
  Vendor\Sitepackage\EventListener\AddCustomerNumber:
    tags:
      - name: event.listener
        identifier: 'sitepackage/webchatagent-customer-number'
```

Values must be strings, numbers or booleans. The chat keeps up to 20 keys and 500 characters per value. Context data is shown to the bot, it is not a login. Don't pass anything you wouldn't show the visitor.

Context data only goes to your own chats: elements with the site's chatbot ID and **Inline chat** content elements (signed with the TYPO3 encryption key). A chat snippet of another chatbot pasted into a page gets none.

## Good to know

- **Server address**: leave it empty. Only fill it in if your chatbot provider gives you its own address (white-label), e.g. `https://chat.example-agency.com`.
- **Content Security Policy**: with frontend CSP enabled, the extension allows the chat server for scripts, connections, images and media, and `'unsafe-inline'` for styles (the widget renders its styles inside its shadow DOM).
- **Static file caches** such as EXT:staticfilecache store pages including the chat. Clear that cache after changing the chat settings.
- **Privacy**: WebChatAgent is an external service. The visitor's browser loads the chat from the server address above (by default `https://webchatagent.com`), which transmits standard request data such as IP address and user agent. Chat messages and anything a visitor enters in the chat are sent there as well. Describe the service in your privacy policy. Questions: privacy@webchatagent.com, [privacy policy](https://webchatagent.com/privacy-policy), [terms](https://webchatagent.com/terms).

## Support

- Documentation: https://docs.webchatagent.com/integrations/typo3
- Issues: https://github.com/webchatagent/typo3-webchatagent/issues

## License

GPL-2.0-or-later
