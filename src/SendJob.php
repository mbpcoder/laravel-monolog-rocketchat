<?php

namespace TheCoder\MonologRocketChat;

use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class SendJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Set the maximum number of retries
    public $tries = 2;

    // Set the retry delay (in seconds)
    public $retryAfter = 120;

    public function __construct(
        private string $url,
        private string $message
    )
    {
    }

    public function handle(): void
    {
        $httpClientOption = [];
        $httpClientOption['verify'] = false;

        $httpClientOption['timeout'] = 5;

        $requestOptions = [
            'form_params' => [
                'text' => $this->message,
            ]
        ];

        $httpClient = new Client($httpClientOption);

        try {
            $response = $httpClient->post($this->url, $requestOptions);
        } catch (\Throwable $exception) {
            $this->fail($exception);
        }
    }
}
