<?php

namespace TheCoder\MonologRocketChat;

use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\LogRecord;

class RocketChatFormatter implements FormatterInterface
{
    const MESSAGE_FORMAT = "*%level_name%* (%channel%) [%date%]\n\n%message%\n\n%context%%extra%";
    const DATE_FORMAT = 'Y-m-d H:i:s e';

    protected bool $html;

    protected string $format;

    protected string $dateFormat;

    protected string $separator;

    protected string|null $tags;

    public function __construct(bool $html = true, string|null $format = null, string|null $dateFormat = null, string $separator = '-', string|null $tags = null)
    {
        $this->html = $html;
        $this->format = $format ?: self::MESSAGE_FORMAT;
        $this->dateFormat = $dateFormat ?: self::DATE_FORMAT;
        $this->separator = $separator;
        $this->tags = $tags;
    }

    public function format($record): string
    {
        $message = '';
        if (isset($record['context']) && isset($record['context']['exception'])) {
            $exception = $record['context']['exception'];
            try {
                $message = $this->getMessageForException($exception);
            } catch (\Exception $e) {
                //
            }
            return $message;
        }

        return $this->getMessageForLog($record);

    }

    public function formatBatch(array $records): string
    {
        $message = '';
        foreach ($records as $record) {
            if (!empty($message)) {
                $message .= str_repeat($this->separator, 15) . PHP_EOL;
            }

            $message .= $this->format($record);
        }

        return $message;
    }

    protected function getTags(): string
    {
        $message = '';
        $tags = explode(',', $this->tags);
        foreach ($tags as $_tag) {
            if (!empty($_tag)) {
                $message .= '#' . $_tag . ' ';
            }
        }
        return $message;
    }

    protected function getMessageForException($exception): string
    {
        $severity = '';
        $request = app('request');
        if (method_exists($exception, 'getSeverity')) {
            $severity = $this->getSeverityName($exception->getSeverity());
        }

        $code = $exception->getCode();
        if (method_exists($exception, 'getStatusCode')) {
            $code = $exception->getStatusCode();
        }

        $message = $severity . ' *Time: * ' . date('Y-m-d H:i:s') . PHP_EOL
            . '*On:* ' . app()->environment() . PHP_EOL
            . '*Message:* ' . $exception->getMessage() . PHP_EOL
            . '*Exception:* ' . get_class($exception) . PHP_EOL
            . '*Code:* ' . $code . PHP_EOL;

        if (!empty($this->tags)) {
            $message .= '*Tag:* ' . $this->getTags() . PHP_EOL;
        }

        $message .= '*File:* ' . $exception->getFile() . PHP_EOL
            . '*Line:* ' . $exception->getLine() . PHP_EOL
            . '*Url:* ' . urldecode(str_replace(['http://', 'https://'],'', $request->url())) . PHP_EOL
            . '*Ip:* ' . $request->getClientIp();

        $user = $request->user();
        if ($user !== null) {
            $message .= PHP_EOL . '*User:* ' . $user->id;
            if (!empty($user->name)) {
                $message .= ' / *Name:* ' . $user->name;
            }
        }

        if (!empty($request->headers->get('referer'))) {
            $message .= PHP_EOL . '*Referer:* ' . $request->headers->get('referer');
        }

        if (!empty($request->getMethod())) {
            $message .= PHP_EOL . '*Request Method:* ' . $request->getMethod();
            if ($request->ajax()) {
                $message .= ' *(Ajax)* ';
            }
        }

        $message .= PHP_EOL . '*Request Inputs:* `' . str_replace(
                ["\n", " ", '<', '>'], ['', '', '&lt;', '&gt;'], json_encode($this->maskSensitiveData($request), JSON_UNESCAPED_UNICODE)
            ) . '`';

        $message .= PHP_EOL . PHP_EOL . '*Trace: * ' . PHP_EOL . '* => * => ' . substr($exception->getTraceAsString(), 0, 1000) . ' ...';

        return $message;
    }

    protected function getMessageForLog($record): string
    {
        $message = $this->format;
        $lineFormatter = new LineFormatter();

        if (strpos($record['message'], 'Stack trace') !== false) {
            // Replace '<' and '>' with their special codes
            $record['message'] = preg_replace('/<([^<]+)>/', '&lt;$1&gt;', $record['message']);

            // Put the stack trace inside <code></code> tags
            $record['message'] = preg_replace('/^Stack trace:\n((^#\d.*\n?)*)$/m', "\n*Stack trace:*\n<code>$1</code>", $record['message']);
        }

        $message = str_replace('%message%', $record['message'], $message);

        if ($record['context']) {
            $context = '*Context:* ';
            $context .= $lineFormatter->stringify($record['context']);
            $message = str_replace('%context%', $context . "\n", $message);
        } else {
            $message = str_replace('%context%', '', $message);
        }

        if ($record['extra']) {
            $extra = '*Extra:* ';
            $extra .= $lineFormatter->stringify($record['extra']);
            $message = str_replace('%extra%', $extra . "\n", $message);
        } else {
            $message = str_replace('%extra%', '', $message);
        }

        $message = str_replace(['%level_name%', '%channel%', '%date%'], [$record['level_name'], $record['channel'], $record['datetime']->format($this->dateFormat)], $message);

        if (!empty($this->tags)) {
            $message .= '*Tag:* ' . $this->getTags() . PHP_EOL;
        }

        if ($this->html === false) {
            $message = strip_tags($message);
        }

        return $message;
    }

    protected function getSeverityName($key): string
    {
        $severities = [
            1 => 'ERROR',
            2 => 'WARNING',
            4 => 'PARSE',
            8 => 'NOTICE',
            16 => 'CORE_ERROR',
            32 => 'CORE_WARNING',
            64 => 'COMPILE_ERROR',
            128 => 'COMPILE_WARNING',
            256 => 'USER_ERROR',
            512 => 'USER_WARNING',
            1024 => 'USER_NOTICE',
            2048 => 'STRICT',
            4096 => 'RECOVERABLE_ERROR',
            8192 => 'DEPRECATED',
            16384 => 'USER_DEPRECATED',
        ];
        if (isset($severities[$key])) {
            return $severities[$key];
        }
        return '';
    }

    protected function maskSensitiveData($request): array
    {
        $sensitiveFields = [
            'username',
            'password',
            'auth',
            'token',
            'key',
            'credential',
            'secret',
            'password_confirmation'
        ];

        $data = $request->except($sensitiveFields);

        $maskedData = $request->only($sensitiveFields);
        foreach ($maskedData as $key => &$value) {
            $value = '*';
        }

        return array_merge($data, $maskedData);
    }
}
