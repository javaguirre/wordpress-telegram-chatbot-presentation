<?php

namespace OdbBot;

use Requests;


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