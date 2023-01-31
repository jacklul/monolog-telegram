<?php

namespace jacklul\MonologTelegramHandler\Tests;

use Dotenv\Dotenv;
use Monolog\Handler\BufferHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use jacklul\MonologTelegramHandler\TelegramHandler;

class TelegramHandlerTest extends TestCase
{
    private ?string $token;
    private ?int $chat_id;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        if (class_exists(Dotenv::class) && file_exists(dirname(__DIR__) . '/.env')) {
            $env = Dotenv::createImmutable(dirname(__DIR__));
            $env->load();
        }

        $this->token = $_ENV['TELEGRAM_TOKEN'] ?? getenv('TELEGRAM_TOKEN');
        $this->chat_id = (int) ($_ENV['TELEGRAM_CHAT_ID'] ?? getenv('TELEGRAM_CHAT_ID'));

        parent::__construct($name, $data, $dataName);
    }

    public function testWithValidArguments(): void
    {
        if (empty($this->token) || empty($this->chat_id)) {
            $this->markTestSkipped('Either token or chat id was not provided');
        }

        $logger = new Logger('PHPUnit');
        $handler = new TelegramHandler($this->token, $this->chat_id, Level::Debug, true, false);
        $logger->pushHandler($handler);

        try {
            $logger->debug('PHPUnit - test with valid arguments: ' . PHP_EOL . str_repeat('-', 4096));
        } catch (\Exception $e) {
            $this->fail('Exception was thrown: ' . $e->getMessage());
        }

        sleep(1);
        
        if (extension_loaded('curl')) {
            $logger = new Logger('PHPUnit');
            $handler = new TelegramHandler($this->token, $this->chat_id, Level::Debug);
            $logger->pushHandler($handler);

            try {
                $logger->debug('PHPUnit - test with valid arguments using cURL: ' . PHP_EOL . str_repeat('-', 4096));
            } catch (\Exception $e) {
                $this->fail('Exception was thrown: ' . $e->getMessage());
            }

            $this->assertTrue(true);
            
            sleep(1);
        }

        $this->assertTrue(true);
        
        $logger = new Logger('PHPUnit');
        $handler = new TelegramHandler($this->token, $this->chat_id, Level::Debug);
        $handler = new BufferHandler($handler);
        $logger->pushHandler($handler);

        try {
            $logger->debug('PHPUnit - Batch processing test 1');
            $logger->debug('PHPUnit - Batch processing test 2');
            $logger->debug('PHPUnit - Batch processing test 3');
            $handler->close();
        } catch (\Exception $e) {
            $this->fail('Exception was thrown: ' . $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function testWithInvalidToken(): void
    {
        sleep(1);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not Found');

        if (extension_loaded('curl')) {
            $logger = new Logger('PHPUnit');
            $handler = new TelegramHandler('token', $this->chat_id, Level::Debug);
            $logger->pushHandler($handler);

            $logger->debug('PHPUnit');
        }

        $logger = new Logger('PHPUnit');
        $handler = new TelegramHandler('token', $this->chat_id, Level::Debug, true, false);
        $logger->pushHandler($handler);

        $logger->debug('PHPUnit');
    }

    public function testWithInvalidChatId(): void
    {
        sleep(1);

        if (empty($this->token)) {
            $this->markTestSkipped('Token was not provided');
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Bad Request');

        if (extension_loaded('curl')) {
            $logger = new Logger('PHPUnit');
            $handler = new TelegramHandler($this->token, 123, Level::Debug);
            $logger->pushHandler($handler);

            $logger->debug('PHPUnit');
        }

        $logger = new Logger('PHPUnit');
        $handler = new TelegramHandler($this->token, 123, Level::Debug, true, false);
        $logger->pushHandler($handler);

        $logger->debug('PHPUnit');
    }
}
