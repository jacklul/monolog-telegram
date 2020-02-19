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
    const BASE_URI = 'https://api.telegram.org';

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
     * Verify SSL certificate?
     *
     * @var bool
     */
    private $verifyPeer;

    /**
     * @param string $token      Telegram bot API token
     * @param int    $chatId     Chat ID to which logs will be sent
     * @param int    $level      The minimum logging level at which this handler will be triggered
     * @param bool   $bubble     Whether the messages that are handled can bubble up the stack or not
     * @param bool   $useCurl    Whether to use cURL extension when available or not
     * @param int    $timeout    Maximum time to wait for requests to finish
     * @param bool   $verifyPeer Whether to use SSL certificate verification or not
     */
    public function __construct($token, $chatId, $level = Logger::DEBUG, $bubble = true, $useCurl = true, $timeout = 10, $verifyPeer = true)
    {
        $this->token = $token;
        $this->chatId = $chatId;
        $this->useCurl = $useCurl;
        $this->timeout = $timeout;
        $this->verifyPeer = $verifyPeer;

        parent::__construct($level, $bubble);
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record): void
    {
        $message = $record['formatted'] ?? $record['message'];

        // When message is too long we have to remove HTML tags so that the message can be properly split
        if (mb_strlen($message, 'UTF-8') > 4096) {
            $message = strip_tags($message);
        }

        // Split the message and send it in parts when needed
        do {
            $message_part = mb_substr($message, 0, 4096);
            $this->send($message_part);
            $message = mb_substr($message, 4096);
        } while ($message !== '');
    }

    /**
     * {@inheritdoc}
     */
    public function handleBatch(array $records): void
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
        $url = self::BASE_URI . '/bot' . $this->token . '/sendMessage';
        $data = [
            'chat_id'                  => $this->chatId,
            'text'                     => $message,
            'disable_web_page_preview' => true // Just in case there is a link in the message
        ];

        // Set HTML parse mode when HTML code is detected
        if (preg_match('/<[^<]+>/', $data['text']) !== false) {
            $data['parse_mode'] = 'HTML';
        }

        if ($this->useCurl === true && extension_loaded('curl')) {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verifyPeer);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

            $result = curl_exec($ch);
            if (!$result) {
                throw new \RuntimeException('Request to Telegram API failed: ' . curl_error($ch));
            }
        } else {
            $opts = [
                'http' => [
                    'method'  => 'POST',
                    'header'  => 'Content-type: application/x-www-form-urlencoded',
                    'content' => http_build_query($data),
                    'timeout' => $this->timeout,
                ],
                'ssl'  => [
                    'verify_peer'      => $this->verifyPeer,
                    'verify_peer_name' => $this->verifyPeer,
                ],
            ];

            $result = @file_get_contents($url, false, stream_context_create($opts));
            if (!$result) {
                $error = error_get_last();
                if (isset($error['message'])) {
                    throw new \RuntimeException('Request to Telegram API failed: ' . $error['message']);
                }

                throw new \RuntimeException('Request to Telegram API failed');
            }
        }

        $result = json_decode($result, true);
        if (isset($result['ok']) && $result['ok'] === true) {
            return true;
        }

        if (isset($result['description'])) {
            throw new \RuntimeException('Telegram API error: ' . $result['description']);
        }

        return false;
    }
}
