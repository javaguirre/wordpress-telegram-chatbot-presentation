<?php

namespace OdbBot;

use Longman\TelegramBot\Entities\InlineKeyboard;


class ConversationService {
    const DEFAULT_MESSAGE = 'Hola!';

    function process($app, $chat_id, $apiai_response) {
        list($output, $with_keyboard) = $this->processIntent(
            $apiai_response,
            $app
        );

        return $this->getResponse($chat_id, $output, $with_keyboard);
    }

    function processIntent($apiai_response, $app) {
        $with_keyboard = true;

        $intentName = $apiai_response['intentName'];
        $parameters = $apiai_response['parameters'];
        $output = $apiai_response['speech'];
        $wordpressService = $app['wordpress_service'];

        switch ($intentName) {
            case 'list':
                $output = $wordpressService->showList();
                break;
            // We should refactor this part, less verbose, but this way
            // it's clear what we do depending on the intent
            case 'show':
                if (array_filter($parameters)) {
                    $output = $wordpressService->show($parameters['id']);
                } else {
                    $with_keyboard = false;
                }
                break;
            case 'create':
                if (array_filter($parameters)) {
                    $wordpressService->create($parameters);
                } else {
                    $with_keyboard = false;
                }
                break;
            case 'edit':
                if (array_filter($parameters)) {
                    $wordpressService->edit($parameters);
                } else {
                    $with_keyboard = false;
                }
                break;
        }

        if (!$output) {
            $output = self::DEFAULT_MESSAGE;
        }

        return array($output, $with_keyboard);
    }

    function getResponse($chat_id, $output, $with_keyboard) {
        $response = [
            'chat_id'    => $chat_id,
            'text'       => $output,
            'parse_mode' => 'Html'
        ];

        if ($with_keyboard) {
            $response['reply_markup'] = $this->getKeyboard();
        }

        return $response;
    }

    function getKeyboard() {
        return new InlineKeyboard([
            ['text' => 'ver', 'callback_data' => 'ver'],
            ['text' => 'lista', 'callback_data' => 'lista'],
            ['text' => 'crear', 'callback_data' => 'crear'],
            ['text' => 'editar', 'callback_data' => 'editar'],
        ]);
    }
}