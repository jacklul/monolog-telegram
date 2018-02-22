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

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
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
    /**
     * Guzzle HTTP client object
     *
     * @var Client
     */
    private $client;

    /**
     * Target chat ID
     *
     * @var int
     */
    private $chat_id;

    /**
     * @param string     $token          Telegram bot API token
     * @param int        $chat_id        Chat ID to which logs will be sent
     * @param int        $level          The minimum logging level at which this handler will be triggered
     * @param array|null $client_options Custom options for Guzzle client
     * @param bool       $bubble         Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct($token, $chat_id, $level = Logger::DEBUG, array $client_options = null, $bubble = true)
    {
        if (!is_string($token)) {
            throw new \InvalidArgumentException('Argument \'token\' must be a string!');
        }

        if (!is_int($chat_id)) {
            throw new \InvalidArgumentException('Argument \'chat_id\' must be an integer!');
        }

        $options = ['base_uri' => 'https://api.telegram.org/bot' . $token . '/'];
        if ($client_options !== null && is_array($client_options)) {
            $options = array_merge($options, $client_options);
        }

        $this->chat_id = $chat_id;
        $this->client = new Client($options);

        parent::__construct($level, $bubble);
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record)
    {
        $data = [
            'chat_id' => $this->chat_id,
            'text' => $record['formatted'] ?: $record['message'],
            'disable_web_page_preview' => true, // In case there is a link in the message, prevent generating preview
        ];

        // Set parse mode when HTML code is detected
        if (preg_match("/<[^<]+>/", $data['text']) !== false) {
            $data['parse_mode'] = 'HTML';
        }

        try {
            $this->client->post('sendMessage', ['form_params' => $data]);
        } catch (RequestException $e) {
            // do nothing...
        }
    }
}
