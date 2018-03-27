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

/**
 * Format a message to HTML output supported by Telegram
 *
 * To disable HTML output use: `new TelegramFormatter(false)`
 */
class TelegramFormatter implements FormatterInterface
{
    private $html;

    /**
     * TelegramFormatter constructor.
     *
     * @param bool $html
     */
    public function __construct($html = true)
    {
        if (!is_bool($html)) {
            throw new \InvalidArgumentException('Argument \'html\' must be a boolean!');
        }

        $this->html = $html;
    }

    /**
     * {@inheritdoc}
     */
    public function format(array $record)
    {
        $record['message'] = preg_replace('/<([^<]+)>/', '&lt;$1&gt;', $record['message']); // Replace <> in existing HTML tags with their HTML codes
        $record['message'] = preg_replace('/^(exception)\s/', 'Exception ', $record['message']); // Capitalize first letter of 'exception'
        $record['message'] = preg_replace('/^Stack trace:\r?\n((?:^\#.*\r?\n?)*{main})$/m', PHP_EOL . '<b>Stack trace:</b>' . PHP_EOL . '<code>$1</code>', $record['message']); // Put the stack trace inside <code></code> tags

        $message = '<b>' . $record['level_name'] . ' [</b>' . $record['channel'] . '<b>]</b> at ' . $record['datetime']->format("Y-m-d H:i:s e") . PHP_EOL . PHP_EOL;
        $message .= $record['message'] . PHP_EOL;
        $message .= PHP_EOL;

        if ($record['context']) {
            $message .= "<b>Context:</b>" . PHP_EOL;

            foreach ($record['context'] as $key => $val) {
                $message .= $key . ' => ' . $this->convertToString($val);
            }

            $message .= PHP_EOL . PHP_EOL;
        }

        if ($record['extra']) {
            $message .= "<b>Extra:</b>" . PHP_EOL;

            foreach ($record['extra'] as $key => $val) {
                $message .= $key . ' => ' . $this->convertToString($val);
            }

            $message .= PHP_EOL . PHP_EOL;
        }

        if (!$this->html) {
            $message = strip_tags($message);
        }

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function formatBatch(array $records)
    {
        $message = '';
        foreach ($records as $record) {
            $message .= $this->format($record);
        }

        return $message;
    }

    /**
     * Convert value to string
     *
     * @param $data
     *
     * @return mixed|string
     */
    private function convertToString($data)
    {
        if (null === $data || is_bool($data)) {
            return var_export($data, true);
        }

        if (is_scalar($data)) {
            return (string) $data;
        }

        if (is_object($data)) {
            return json_encode((array) $data);
        }

        return json_encode($data);
    }
}
