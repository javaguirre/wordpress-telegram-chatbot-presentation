<?php

require __DIR__ . '/vendor/autoload.php';

use Monolog\Logger;

define("BASE_WEBHOOK_URL", getenv('BASE_WEBHOOK_URL'));
define("TELEGRAM_BOT_KEY", getenv('TELEGRAM_BOT_KEY'));
define("TELEGRAM_BOT_NAME", getenv('TELEGRAM_BOT_NAME'));
define('WEBHOOK_URL', BASE_WEBHOOK_URL . '/webhook');
define('WORDPRESS_API_URL', getenv('WORDPRESS_API_URL') . '/wp-json/wp/v2/');
define('APIAI_TOKEN', getenv('APIAI_TOKEN'));
define('WP_USERNAME', getenv('WP_USERNAME'));
define('WP_PASSWORD', getenv('WP_PASSWORD'));


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

        return json_decode($response->body, true);
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
        $output = array();

        $request = Requests::get($this->url . 'posts', $this->headers);

        $posts = json_decode($request->body, true);

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
    private $intents = array('create', 'get', 'list');
}

$hook_url = WEBHOOK_URL;
$log = new Logger('name');

$app = new Silex\Application();
$app['debug'] = true;
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => 'php://stderr'
));

$app['telegram_service'] = function () use ($log) {
    return new TelegramService(TELEGRAM_BOT_KEY, TELEGRAM_BOT_NAME);
};

$app['wordpress_service'] = function () use ($log) {
    return new WordpressApiService(WORDPRESS_API_URL, WP_USERNAME, WP_PASSWORD);
};

$app['apiai_service'] = function () use ($log) {
    return new ApiAiService(APIAI_TOKEN);
};

$app->get('/init', function() use ($app, $log, $hook_url) {
    $service = $app['telegram_service'];
    $service->setWebhook($hook_url);
    return $app->json(array('init' => 'telegram'));
});

$app->post('/webhook', function(Symfony\Component\HttpFoundation\Request $request) use ($app, $log) {
    $data = json_decode($request->getContent(), true);
    $app['telegram_service']->handle();
    $apiai_response = $app['apiai_service']->send($data['message']['text']);
    $log->info(json_encode($apiai_response));
    $intentName = $apiai_response['result']['metadata']['intentName'];
    $parameters = $apiai_response['result']['parameters'];

    switch ($intentName) {
    case 'list':
        $output = $app['wordpress_service']->showList();
        break;
    case 'show':
        if (array_filter($parameters)) {
            $output = $app['wordpress_service']->show($parameters['id']);
        } else {
            $output = $apiai_response['result']['speech'];
        }
        break;
    case 'create':
        if (array_filter($parameters)) {
            $app['wordpress_service']->create($parameters);
        }
        $output = $apiai_response['result']['speech'];
        break;
    case 'edit':
        if (array_filter($parameters)) {
            $app['wordpress_service']->edit($parameters);
        }
        $output = $apiai_response['result']['speech'];
        break;
    }

    $response = [
        'chat_id'    => $data['message']['chat']['id'],
        'text'       => $output,
        'parse_mode' => 'Html'
    ];
    $result = Longman\TelegramBot\Request::sendMessage($response);
    $log->info($result);

    return $app->json(array('webhook' => 'ok'));
});

$app->run();

?>