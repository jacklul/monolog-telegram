<?php

namespace jacklul\MonologTelegramHandler\Tests;

use jacklul\MonologTelegramHandler\TelegramFormatter;
use PHPUnit\Framework\TestCase;

class TelegramFormatterTest extends TestCase
{
    private $record;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        $this->record = [
            'message' => 'PHP Fatal error:  Uncaught Exception: test in /tmp/test/test.php:17
Stack trace:
 #0 /tmp/test/testclass.php(91): send()
 #1 /tmp/test/testclass.php(31): prepare()
 #2 {main}
  thrown in /tmp/test/test.php on line 17',
            'context' => ['type' => 'exception'],
            'extra' => ['extra' => 'parameter'],
            'datetime' => new \DateTime(),
            'level_name' => 'ERROR',
            'channel' => 'test channel',
        ];

        parent::__construct($name, $data, $dataName);
    }

    public function testFormatter()
    {
        $formatter = new TelegramFormatter();

        $output = $formatter->format($this->record);

        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('<b>ERROR</b> (test channel) ', $output);
            $this->assertStringContainsString('<b>Context:</b> {"type":"exception"}', $output);
            $this->assertStringContainsString('<b>Extra:</b> {"extra":"parameter"}', $output);
        } elseif (method_exists($this, 'assertContains')) {
            $this->assertContains('<b>ERROR</b> (test channel) ', $output);
            $this->assertContains('<b>Context:</b> {"type":"exception"}', $output);
            $this->assertContains('<b>Extra:</b> {"extra":"parameter"}', $output);
        }

        $formatter = new TelegramFormatter(false);

        $output = $formatter->format($this->record);

        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('ERROR (test channel) ', $output);
            $this->assertStringContainsString('Context: {"type":"exception"}', $output);
            $this->assertStringContainsString('Extra: {"extra":"parameter"}', $output);
        } elseif (method_exists($this, 'assertContains')) {
            $this->assertContains('ERROR (test channel) ', $output);
            $this->assertContains('Context: {"type":"exception"}', $output);
            $this->assertContains('Extra: {"extra":"parameter"}', $output);
        }
    }
}
