<?php

namespace jacklul\MonologTelegramHandler\Tests;

use Dotenv\Dotenv;
use jacklul\MonologTelegramHandler\TelegramHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class TelegramHandlerTest extends TestCase
{
    private $token;
    private $chat_id;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        if (class_exists(Dotenv::class) && file_exists(dirname(__DIR__) . '/.env')) {
            $env = Dotenv::create(dirname(__DIR__));
            $env->load();
        }

        $this->token = getenv('TELEGRAM_TOKEN');
        $this->chat_id = getenv('TELEGRAM_CHAT_ID');

        parent::__construct($name, $data, $dataName);
    }

    public function testWithValidArguments()
    {
        if (empty($this->token) || empty($this->chat_id)) {
            $this->markTestSkipped('Either token or chat id was not provided');
        }

        if (extension_loaded('curl')) {
            $logger = new Logger('PHPUnit');
            $handler = new TelegramHandler($this->token, $this->chat_id, Logger::DEBUG, true, true, 5, true);
            $logger->pushHandler($handler);

            $result = $logger->debug('PHPUnit' . str_repeat('-', 4096));
            $this->assertTrue($result);
        }

        $logger = new Logger('PHPUnit');
        $handler = new TelegramHandler($this->token, $this->chat_id, Logger::DEBUG, true, false, 5, (float)PHP_VERSION >= 5.6);
        $logger->pushHandler($handler);

        $result = $logger->debug('PHPUnit' . str_repeat('-', 4096));
        $this->assertTrue($result);
    }

    public function testWithInvalidToken()
    {
        if (method_exists($this, 'expectException') && method_exists($this, 'expectExceptionMessage')) {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Not Found');
        } elseif (method_exists($this, 'setExpectedException')) {
            $this->setExpectedException(\RuntimeException::class, 'Not Found');
        }

        if (extension_loaded('curl')) {
            $logger = new Logger('PHPUnit');
            $handler = new TelegramHandler('token', $this->chat_id, Logger::DEBUG, true, true, 5, true);
            $logger->pushHandler($handler);

            $logger->debug('PHPUnit');
        }

        $logger = new Logger('PHPUnit');
        $handler = new TelegramHandler('token', $this->chat_id, Logger::DEBUG, true, false, 5, (float)PHP_VERSION >= 5.6);
        $logger->pushHandler($handler);

        $logger->debug('PHPUnit');
    }

    public function testWithInvalidChatId()
    {
        if (empty($this->token)) {
            $this->markTestSkipped('Token was not provided');
        }

        if (method_exists($this, 'expectException') && method_exists($this, 'expectExceptionMessage')) {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Bad Request');
        } elseif (method_exists($this, 'setExpectedException')) {
            $this->setExpectedException(\RuntimeException::class, 'Bad Request');
        }

        if (extension_loaded('curl')) {
            $logger = new Logger('PHPUnit');
            $handler = new TelegramHandler($this->token, 123, Logger::DEBUG, true, true, 5, true);
            $logger->pushHandler($handler);

            $logger->debug('PHPUnit');
        }

        $logger = new Logger('PHPUnit');
        $handler = new TelegramHandler($this->token, 123, Logger::DEBUG, true, false, 5, (float)PHP_VERSION >= 5.6);
        $logger->pushHandler($handler);

        $logger->debug('PHPUnit');
    }
}
