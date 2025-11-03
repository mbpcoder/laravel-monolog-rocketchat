# Real-Time Laravel exception logs in your Rocket Chat  🚀

## ScreenShot
<img width="508" height="540" alt="image" src="https://github.com/user-attachments/assets/732ddfbc-cbc7-4826-ae37-9ae449dc0b13" />


## ❓ Why Another Logger?

Logging should be more than just saving data — it should **drive action**. Here’s why 🔔 **Rocket Chat Handler for Monolog** 📝 is a game-changer:

- 🚀 **Real-Time Feedback** – Instantly receive logs in your Rocket Chat.
- 🧠 **Actionable Insights** – Include useful context for quick understanding.
- 🛡️ **No Need for Sentry or Third-Party Services**
- ⚡ **Immediate Alerts** – Be notified of issues the moment they happen.
- 👥 **Team Collaboration** – Share logs in group chats for quick follow-up.

## 🎯 Installation

Install via Composer:

```sh
composer require mbpcoder/laravel-monolog-rocketchat
```

## ⚙️ Usage

Update your `config/logging.php` file to configure the Rocket Chat logging channel.

### ⏳ Running Logs in a Queue

If a queue name is set, logs will be processed asynchronously in the specified queue. Otherwise, they will run synchronously.

### 🔧 Configuration Example

Modify your `config/logging.php` file:

```php
return [
    'channels' => [
        'stack' => [
            'driver'   => 'stack',
            'channels' => ['single', 'rocketchat'],
        ],

        'rocketchat' => [
            'driver' => 'monolog',
            'level' => 'debug',
            'handler' => TheCoder\MonologRocketChat\RocketChatBotHandler::class,
            'handler_with' => [
                'token' => env('LOG_ROCKET_CHAT_BOT_TOKEN'),
                'chat_id' => env('LOG_ROCKET_CHAT_CHAT_ID'),
                'api_url' => env('LOG_ROCKET_CHAT_BOT_API'),
                'queue' => env('LOG_ROCKET_CHAT_QUEUE', null)
                'queue_connection' => env('LOG_ROCKET_CHAT_QUEUE_CONNECTION', null),
            ],
            'formatter' => TheCoder\MonologRocketChat\RocketChatFormatter::class,
            'formatter_with' => [
                'tags' => env('LOG_ROCKET_CHAT_TAGS', null),
            ],
        ],
    ],
];
```
### 🔄 Customizing Log Settings at Runtime

You can dynamically set the bot token, chat ID while logging:

```php
logger('message', [
    'token' => 'your_bot_token',
    'chat_id' => 'your_chat_id'
]);
```

## 📜 Environment Variables

Ensure the following variables are set in your `.env` file:

```ini
LOG_ROCKET_CHAT_BOT_TOKEN=
LOG_ROCKET_CHAT_CHAT_ID=
LOG_ROCKET_CHAT_BOT_API=

```

## 📄 License

This package is open-source and available under the MIT License. 🏆

