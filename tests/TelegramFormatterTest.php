<?php

namespace jacklul\MonologTelegramHandler\Tests;

use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use jacklul\MonologTelegramHandler\TelegramFormatter;

class TelegramFormatterTest extends TestCase
{
    private LogRecord $record;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        $this->record = new LogRecord(
            new \DateTimeImmutable(),
            'test channel',
            Level::Error,
            'PHP Fatal error:  Uncaught Exception: test in /tmp/test/test.php:17
Stack trace:
 #0 /tmp/test/testclass.php(91): send()
 #1 /tmp/test/testclass.php(31): prepare()
 #2 {main}
  thrown in /tmp/test/test.php on line 17',
            ['type' => 'exception'],
            ['extra' => 'parameter']
        );

        parent::__construct($name, $data, $dataName);
    }

    public function testFormatter(): void
    {
        $formatter = new TelegramFormatter();
        $output = $formatter->format($this->record);

        $this->assertStringContainsString('<b>ERROR</b> (test channel) ', $output);
        $this->assertStringContainsString('<b>Context:</b> {"type":"exception"}', $output);
        $this->assertStringContainsString('<b>Extra:</b> {"extra":"parameter"}', $output);

        $formatter = new TelegramFormatter(false);
        $output = $formatter->format($this->record);

        $this->assertStringContainsString('ERROR (test channel) ', $output);
        $this->assertStringContainsString('Context: {"type":"exception"}', $output);
        $this->assertStringContainsString('Extra: {"extra":"parameter"}', $output);
    }
}
