<?php

namespace OdbBot\Services;


class TelegramService {
    private $telegram;

    function __construct($key, $name) {
        $this->telegram = new Longman\TelegramBot\Telegram($key, $name);
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


class ApiAiService {
    private $url;
    private $headers;

    function __construct($token) {
        $this->url = 'https://api.api.ai/v1/query';
        $this->headers = [
            'Authorization' => 'Bearer ' . $token,
            'Content-type'  => 'application/json'
        ];
    }

    function send($text) {
        $sessionId = 'mysession';
        $response = Requests::post(
            $this->url,
            $this->headers,
            json_encode(
                array(
                    'lang'       => 'es',
                    'sessionId'  => $sessionId,
                    'query'      => $text,
                    'action'     => 'post',
                    'parameters' => $parameters
                )
            )
        );

        $body = json_decode($response->body, true);

        return array(
            'intentName' => $body['result']['metadata']['intentName'],
            'parameters' => $body['result']['parameters'],
            'speech'     => $body['result']['speech']
        );
    }
}


class WordpressApiService {
    private $url;

    function __construct($url, $username, $password) {
        $this->url = $url;
        $authorization = base64_encode($username . ':' . $password);
        $this->headers = [
            'Content-type'  => 'application/json',
            'Authorization' => 'Basic ' . $authorization
        ];
    }

    function showList() {
        $request = Requests::get($this->url . 'posts', $this->headers);
        $posts = json_decode($request->body, true);
        return $this->getListFormatted($posts);
    }

    function getListFormatted($posts) {
        $output = array();

        foreach ($posts as $post) {
            $post_pretty = sprintf(
                '<b>%d %s</b> <a href="%s">enlace</a> %s',
                $post['id'],
                $post['title']['rendered'],
                $post['link'],
                chr(10) . chr(10)
            );
            array_push($output, $post_pretty);
        }

        return implode(' ', $output);
    }

    function create($parameters) {
        $output = array();
        $data = json_encode(
            array(
                'title'     => $parameters['title'],
                'content'   => $parameters['content'],
                'status'    => 'publish'
            )
        );

        $response = Requests::post(
            $this->url . 'posts',
            $this->headers,
            $data
        );

        return $response->body;
    }

    function show($id) {
        $response = Requests::get(
            $this->url . 'posts/' . $id,
            $this->headers
        );
        $post = json_decode($response->body, true);
        return $this->getShowFormatted($post);
    }

    function getShowFormatted($post) {
        $post_pretty = sprintf(
            '<b>%d %s</b> <a href="%s">enlace</a> %s <pre>%s</pre>',
            $post['id'],
            $post['title']['rendered'],
            $post['link'],
            chr(10) . chr(10),
            strip_tags($post['excerpt']['rendered'])
        );

        return $post_pretty;
    }

    function edit($parameters) {
        $output = array();
        $data = json_encode(
            array(
                'title'     => $parameters['title'],
                'content'   => $parameters['content'],
                'status'    => 'publish'
            )
        );

        $response = Requests::put(
            $this->url . 'posts/' . $parameters['id'],
            $this->headers,
            $data
        );

        return $response->body;
    }
}


class ConversationService {
    const DEFAULT_MESSAGE = 'Hola!';

    function process($app, $chat_id, $apiai_response) {
        list($output, $with_keyboard) = $this->processIntent(
            $apiai_response,
            $app
        );

        return $this->getResponse($chat_id, $output);
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

    function getResponse($chat_id, $output) {
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
        return new Longman\TelegramBot\Entities\InlineKeyboard([
            ['text' => 'ver', 'callback_data' => 'ver'],
            ['text' => 'lista', 'callback_data' => 'lista'],
            ['text' => 'crear', 'callback_data' => 'crear'],
            ['text' => 'editar', 'callback_data' => 'editar'],
        ]);
    }
}