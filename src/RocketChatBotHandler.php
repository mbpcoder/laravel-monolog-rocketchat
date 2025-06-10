<?php

namespace TheCoder\MonologRocketChat;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;

class RocketChatBotHandler extends AbstractProcessingHandler
{
    protected const ROCKET_CHAT_MESSAGE_SIZE = 4096;

    protected string $apiUrl;

    protected string $token;

    protected string|int $chatId;

    protected string|null $queue = null;

    public function __construct(
        Level       $level,
        string      $api_url,
        string      $token,
        string|int  $chat_id,
        string|null $queue = null
    )
    {
        parent::__construct();

        $this->level = $level;
        $this->apiUrl = $api_url;
        $this->token = $token;
        $this->chatId = $chat_id;
        $this->queue = $queue;
    }

    protected function write($record): void
    {
        $token = $record['context']['token'] ?? null;
        $chatId = $record['context']['chat_id'] ?? null;

        $this->send($record['formatted'], $token, $chatId);
    }

    private function truncateTextToRocketChatLimit(string $textMessage): string
    {
        if (mb_strlen($textMessage) <= self::ROCKET_CHAT_MESSAGE_SIZE) {
            return $textMessage;
        }

        return mb_substr($textMessage, 0, self::ROCKET_CHAT_MESSAGE_SIZE, 'UTF-8');
    }

    protected function send(string $message, $token = null, $chatId = null): void
    {
        $token = $token ?? $this->token;
        $chatId = $chatId ?? $this->chatId;

        $url = $this->apiUrl . '/hooks/' . $chatId . '/' . $token;

        $message = $this->truncateTextToRocketChatLimit($message);

        if (empty($this->queue) || empty($this->queue_connection)) {
            dispatch_sync(new SendJob($url, $message));
        } else {
            dispatch(new SendJob($url, $message))->onConnection($this->queue_connection)->onQueue($this->queue);
        }
    }

}
