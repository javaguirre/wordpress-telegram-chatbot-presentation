<?php

namespace OdbBot;

use Longman\TelegramBot\Entities\InlineKeyboard;


class ConversationService {
    const DEFAULT_MESSAGE = 'Hola!';

    function process($app, $chatId, $apiaiResponse) {
        list($output, $withKeyboard) = $this->processIntent(
            $apiaiResponse,
            $app
        );

        return $this->getResponse($chatId, $output, $withKeyboard);
    }

    function processIntent($apiaiResponse, $app) {
        $withKeyboard = true;

        $intentName = $apiaiResponse['intentName'];
        $parameters = $apiaiResponse['parameters'];
        $output = $apiaiResponse['speech'];
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
                    $withKeyboard = false;
                }
                break;
            case 'create':
                if (array_filter($parameters)) {
                    $wordpressService->create($parameters);
                } else {
                    $withKeyboard = false;
                }
                break;
            case 'edit':
                if (array_filter($parameters)) {
                    $wordpressService->edit($parameters);
                } else {
                    $withKeyboard = false;
                }
                break;
        }

        if (!$output) {
            $output = self::DEFAULT_MESSAGE;
        }

        return array($output, $withKeyboard);
    }

    function getResponse($chatId, $output, $withKeyboard) {
        $response = [
            'chat_id'    => $chatId,
            'text'       => $output,
            'parse_mode' => 'Html'
        ];

        if ($withKeyboard) {
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