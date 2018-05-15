# Monolog Telegram Handler

Send your logs through Telegram bot to any chat and make them look fancy!

#### Features:
- [Continous logging](https://raw.githubusercontent.com/jacklul/monolog-telegram/master/screenshot1.jpg) and [batch logging](https://raw.githubusercontent.com/jacklul/monolog-telegram/master/screenshot2.jpg) support
- Standard stack traces wrapped in `<code></code>` tags
- Automatic splitting of the message when it exceeds maximum limit

## Prerequisites

 - Telegram Bot API token - [see here](https://core.telegram.org/bots#creating-a-new-bot) to learn how to obtain one
 - ID of the chat to which you want to send the logs - see below
 
#### Obtaining chat ID

One of the simplest ways to do that is to interact with the bot in the target chat:
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

$api_token = '123456789:teMbvbETojnSG93jDhnynvH8pT28H9TIB1h';    // Bot API token
$chat_id = 987654321;    // Target Chat ID
$level = Logger::ERROR;     // Log level
$use_curl = true;    // Use cURL or not? (default: use when available)
$timeout = 10;   // Timeout for API requests

$logger = new Logger('My project');
$handler = new TelegramHandler($api_token, $chat_id, $level, $use_curl, $timeout);
$handler->setFormatter(new TelegramFormatter());    // Usage of this formatter is optional but recommended if you want better message layout
$logger->pushHandler($handler);

$logger->error('Error!');
```

To prevent spamming the chat and hitting Telegram's API limits it is advised to use
 [DeduplicationHandler](https://github.com/Seldaek/monolog/blob/master/src/Monolog/Handler/DeduplicationHandler.php) and/or [BufferHandler](https://github.com/Seldaek/monolog/blob/master/src/Monolog/Handler/BufferHandler.php), ideal solution for production use would be:

```php
$handler = new TelegramHandler('TOKEN', 123456789);
$handler->setFormatter(new TelegramFormatter());

// Combine all log entries into one and force batch processing
$handler = new BufferHandler($handler);

// Make sure that particular log stack wasn't sent before
$handler = new DeduplicationHandler($handler);

// Keep collecting logs until ERROR occurs, after that send collected logs to $handler
$handler = new FingersCrossedHandler($handler, new ErrorLevelActivationStrategy(Logger::ERROR));

$logger->pushHandler($handler);
```

You can customize the formatter:

```php
$html = true;    // Choose whether to send the message in HTMl format
$format = "<b>%level_name%</b> (%channel%) [%date%]\n\n%message%\n\n%context%%extra%";   // Error (My project) [2018-05-01 15:55:15 UTC]
$date_format = 'Y-m-d H:i:s e';       // 2018-05-01 15:55:15 UTC, format must be supported by DateTime::format
$separator = '-';       // Seperation character for batch processing - when empty one empty line is used

$handler->setFormatter(new TelegramFormatter($html, $format, $date_format, $separator));
```

## License

See [LICENSE](LICENSE).
