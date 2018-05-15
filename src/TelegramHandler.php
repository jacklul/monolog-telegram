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

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * Sends logs to chats through Telegram bot
 *
 * $logger = new Logger('telegram-logger');
 * $handler = new TelegramHandler('123456789:teMbvbETojnSG93jDhnynvH8pT28H9TIB1h', 1234567);
 * $handler->setFormatter(new TelegramFormatter());
 * $logger->pushHandler($handler);
 */
class TelegramHandler extends AbstractProcessingHandler
{
    const BASE_URI = 'https://api.telegram.org/bot';

    /**
     * Bot API token
     *
     * @var string
     */
    private $token;

    /**
     * Chat ID
     *
     * @var int
     */
    private $chatId;

    /**
     * Use cURL extension?
     *
     * @var bool
     */
    private $useCurl;

    /**
     * Timeout for requests
     *
     * @var int
     */
    private $timeout;

    /**
     * @param string $token   Telegram bot API token
     * @param int    $chatId  Chat ID to which logs will be sent
     * @param int    $level   The minimum logging level at which this handler will be triggered
     * @param bool   $bubble  Whether the messages that are handled can bubble up the stack or not
     * @param bool   $useCurl Whether to use cURL extension when available or not
     * @param int    $timeout Maximum time to wait for requests to finish
     */
    public function __construct($token, $chatId, $level = Logger::DEBUG, $bubble = true, $useCurl = true, $timeout = 10)
    {
        $this->token = $token;
        $this->chatId = $chatId;
        $this->useCurl = $useCurl;
        $this->timeout = $timeout;

        parent::__construct($level, $bubble);
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record)
    {
        $message = isset($record['formatted']) ? $record['formatted'] : $record['message'];

        // When message is too long we have to remove HTML tags so that the message can be properly split
        if (mb_strlen($message, 'UTF-8') > 4096) {
            $message = strip_tags($message);
        }

        // Split the message and send it in parts when needed
        do {
            $message_part = mb_substr($message, 0, 4096);
            $this->send($message_part);
            $message = mb_substr($message, 4096);
        } while (mb_strlen($message, 'UTF-8') > 0);
    }

    /**
     * {@inheritdoc}
     */
    public function handleBatch(array $records)
    {
        $messages = [];
        foreach ($records as $record) {
            if ($record['level'] < $this->level) {
                continue;
            }

            $messages[] = $this->processRecord($record);
        }

        if (!empty($messages)) {
            $this->write(['formatted' => $this->getFormatter()->formatBatch($messages)]);
        }
    }

    /**
     * Send sendMessage request to Telegram Bot API
     *
     * @param string $message The message to send
     *
     * @return bool
     */
    private function send($message)
    {
        $data = [
            'chat_id'                  => $this->chatId,
            'text'                     => $message,
            'disable_web_page_preview' => true // Just in case there is a link in the message, prevent generating preview
        ];

        // Set special parse mode when HTML code is detected
        if (preg_match("/<[^<]+>/", $data['text']) !== false) {
            $data['parse_mode'] = 'HTML';
        }

        $url = self::BASE_URI . $this->token . '/sendMessage';
        try {
            if ($this->useCurl === true && extension_loaded('curl')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
                $result = curl_exec($ch);
            } else {
                $opts = [
                    'http' => [
                        'method'  => 'POST',
                        'header'  => 'Content-type: application/x-www-form-urlencoded',
                        'content' => http_build_query($data),
                        'timeout' => $this->timeout,
                    ],
                    'ssl'  => [
                        'verify_peer' => false,
                    ],
                ];
                $result = file_get_contents($url, false, stream_context_create($opts));
            }

            $result = json_decode($result, true);
            if (isset($result['ok']) && $result['ok'] === true) {
                return true;
            } elseif (isset($result['description'])) {
                trigger_error('TelegramHandler: API error: ' . $result['description'], E_USER_DEPRECATED);
            }
        } catch (\Exception $e) {
            trigger_error('TelegramHandler: Request exception: ' . $e->getMessage(), E_USER_DEPRECATED);
        }

        return false;
    }
}
