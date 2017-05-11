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
        $this->url = 'https://api.api.ai/v1/query?lang=es&sessionId=';
        $this->headers = [
            'Authorization' => 'Bearer ' . $token
        ];
    }

    function send($text) {
        $sessionId = uniqid();
        $response = Requests::get(
            $this->url . $sessionId . '&query='. $text,
            $this->headers
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
            'Accept' => 'application/json',
            'Authorization' => 'Basic ' . $authorization
        ];
    }

    function get() {
        $output = array();

        $request = Requests::get($this->url . 'posts', $this->headers);

        $posts = json_decode($request->body, true);

        foreach ($posts as $post) {
            $post_pretty = sprintf(
                '*%s* [link](%s)', $post['title']['rendered'], $post['link']);
            array_push($output, $post_pretty);
        }

        return implode('\n', $output);
    }

    function create($title, $content, $tags) {
        $output = array();
        $data = json_encode(
            array(
                'title'     => $title,
                'content'   => $content,
                'status'    => 'publish',
                'tags'      => $tags
            )
        );

        $request = Requests::post(
            $this->url . 'posts',
            $this->headers,
            $data
        );

        return implode('\n', $output);
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

    if ($intentName == 'lista') {
        $output = $app['wordpress_service']->get();
    } elseif ($intentName == 'crear') {
        $log->info('CREATE');
        // $output = $app['wordpress_service']->create();
        $output = $apiai_response['result']['speech'];
    } else {
        $output = $apiai_response['result']['speech'];
    }

    $response = [
        'chat_id'    => $data['message']['chat']['id'],
        'text'       => $output,
        'parse_mode' => 'Markdown'
    ];
    $result = Longman\TelegramBot\Request::sendMessage($response);
    $log->info($result);

    return $app->json(array('webhook' => 'ok'));
});

$app->run();

?>