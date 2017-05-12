<?php

namespace OdbBot;

use Longman\TelegramBot\Telegram;


class TelegramService {
    private $telegram;

    function __construct($key, $name) {
        $this->telegram = new Telegram($key, $name);
    }

    function setWebhook($hook_url) {
        $result = $this->telegram->setWebhook($hook_url);

        if ($result->isOk()) {
            echo $result->getDescription();
        }
    }

    function handle() {
        $this->telegram->handle();
    }

    function getBasicData($data) {
        if (array_key_exists('message', $data)) {
            $text = $data['message']['text'];
            $chat_id = $data['message']['chat']['id'];
        } else {
            $text = $data['callback_query']['data'];
            $chat_id = $data['callback_query']['message']['chat']['id'];
        }

        return array('text' => $text, 'chat_id' => $chat_id);
    }
}