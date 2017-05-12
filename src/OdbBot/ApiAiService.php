<?php

namespace OdbBot;


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