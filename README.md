# Monolog Telegram Handler

Send your logs through Telegram bot to any chat and make them look fancy!

![Screenshot](https://i.imgur.com/ISWxisv.jpg)

## Prerequisites

 - Telegram Bot API token - [see here](https://core.telegram.org/bots#creating-a-new-bot) to learn how to obtain one
 - ID of the chat to which you want to send the logs - see below
 
#### Obtaining chat ID

One of the simpliest ways to do that is to interact with the bot in the target chat:
- private and group chats - send any dummy command
- channels - post something in it

After interacting visit `https://api.telegram.org/botTOKEN/getUpdates` (replace `TOKEN` with your actual bot token), you will be able to find the chat id (`chat_id`) in the result JSON.

## Installation

Install with [Composer](https://github.com/composer/composer):

```bash
$ composer require jacklul/monolog-telegram
```

## Usage

To use this handler you just have to add it like every other **Monolog** handler:

```php
require 'vendor/autoload.php';

$api_token = '123456789:teMbvbETojnSG93jDhnynvH8pT28H9TIB1h';
$chat_id = 987654321;

$logger = new Logger('My project');
$handler = new TelegramHandler($api_token, $chat_id, Logger::ERROR);
$handler->setFormatter(new TelegramFormatter());    // Usage of this formatter is optional but recommended if you want better message layout
$logger->pushHandler($handler);

$logger->error('Error!');
```

By default all messages are sent in HTML format, you can force a normal text format while keeping same message layout:

```php
$handler->setFormatter(new TelegramFormatter(false));
```

It is advised to use the handler through **Monolog**'s [DeduplicationHandler](https://github.com/Seldaek/monolog/blob/master/src/Monolog/Handler/DeduplicationHandler.php) to avoid flooding the chat (and hitting Telegram API limits):

```php
$handler = new TelegramHandler('TOKEN', 123456789);
$logger->pushHandler(new DeduplicationHandler($handler));
```

You can set custom options that are passed to [Guzzle](https://github.com/guzzle/guzzle)'s client:

```php
$handler = new TelegramHandler('TOKEN', 123456789, ['timeout' => 10, 'handler' => new StreamHandler()]);
```

## License

See [LICENSE](LICENSE).
