<?php

/**
 * This file is part of the Monolog Telegram Handler package.
 *
 * (c) Jack'lul <jacklulcat@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace jacklul\MonologTelegramHandler;

use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\LogRecord;

/**
 * Formats a message to output suitable for Telegram chat
 */
class TelegramFormatter implements FormatterInterface
{
    const MESSAGE_FORMAT = "%emoji% <b>%level_name%</b> (%channel%) [%date%]\n\n%message%\n\n%context%%extra%";
    const DATE_FORMAT = 'Y-m-d H:i:s e';

    /**
     * @var bool
     */
    private $html;

    /**
     * @var string
     */
    private $format;

    /**
     * @var string
     */
    private $dateFormat;

    /**
     * @var string
     */
    private $separator;

    /**
     * @var array
     */
    private $emojis = [
        'DEBUG'     => '🐞',
        'INFO'      => 'ℹ️',
        'NOTICE'    => '📌',
        'WARNING'   => '⚠️',
        'ERROR'     => '❌',
        'CRITICAL'  => '💀',
        'ALERT'     => '🛎️',
        'EMERGENCY' => '🚨',
    ];

    /**
     * Formatter constructor
     *
     * @param bool   $html       Format as HTML or not
     * @param string $format     The format of the message
     * @param string $dateFormat The format of the timestamp: one supported by DateTime::format
     * @param string $separator  Record separator used when sending batch of logs in one message
     * @param array  $emojiArray Array containing emojis for each record level name
     */
    public function __construct(bool $html = true, ?string $format = null, ?string $dateFormat = null, string $separator = '-', ?array $emojiArray = null)
    {
        $this->html = $html;
        $this->format = $format ?: self::MESSAGE_FORMAT;
        $this->dateFormat = $dateFormat ?: self::DATE_FORMAT;
        $this->separator = $separator;
        $emojiArray !== null && $this->emojis = array_replace($this->emojis, $emojiArray);
    }

    /**
     * {@inheritdoc}
     */
    public function format(LogRecord $record): string
    {
        $message = $this->format;
        $lineFormatter = new LineFormatter();

        $tmpmessage = preg_replace('/<([^<]+)>/', '&lt;$1&gt;', $record['message']); // Replace '<' and '>' with their special codes
        $tmpmessage = preg_replace('/^Stack trace:\n((^#\d.*\n?)*)$/m', "\n<b>Stack trace:</b>\n<code>$1</code>", $tmpmessage); // Put the stack trace inside <code></code> tags
        $message = str_replace('%message%', $tmpmessage, $message);

        if ($record['context']) {
            $context = '<b>Context:</b> ';
            $context .= $lineFormatter->stringify($record['context']);
            $message = str_replace('%context%', $context . "\n", $message);
        } else {
            $message = str_replace('%context%', '', $message);
        }

        if ($record['extra']) {
            $extra = '<b>Extra:</b> ';
            $extra .= $lineFormatter->stringify($record['extra']);
            $message = str_replace('%extra%', $extra . "\n", $message);
        } else {
            $message = str_replace('%extra%', '', $message);
        }

        $emoji = $this->emojis[$record['level_name']] ?? $this->emojis['DEFAULT'] ?? '🐞';

        /** @param \DateTimeImmutable $record['datetime'] */
        $message = str_replace(['%emoji%', '%level_name%', '%channel%', '%date%'], [$emoji, $record['level_name'], $record['channel'], $record['datetime']->format($this->dateFormat)], $message);

        if ($this->html === false) {
            $message = strip_tags($message);
        }

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function formatBatch(array $records): string
    {
        $message = '';
        foreach ($records as $record) {
            if (!empty($message)) {
                $message .= str_repeat($this->separator, 15) . "\n";
            }

            $message .= $this->format($record);
        }

        return $message;
    }
}
