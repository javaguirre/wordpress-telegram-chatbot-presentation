<?php

require __DIR__ . '/vendor/autoload.php';

use Monolog\Logger;

define("BASE_WEBHOOK_URL", getenv('BASE_WEBHOOK_URL'));
define("TELEGRAM_BOT_KEY", getenv('TELEGRAM_BOT_KEY'));
define("TELEGRAM_BOT_NAME", getenv('TELEGRAM_BOT_NAME'));
define('WEBHOOK_URL', BASE_WEBHOOK_URL . '/webhook');
define('WORDPRESS_API_URL', getenv('WORDPRESS_API_URL'));

class TelegramService {
    var $telegram;

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

$hook_url = WEBHOOK_URL;
$log = new Logger('name');

$app = new Silex\Application();
$app['debug'] = true;
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => 'php://stderr'
));

$app['telegram_service'] = function () use($log) {
    $log->info('Telegram init');
    return new TelegramService(TELEGRAM_BOT_KEY, TELEGRAM_BOT_NAME);
};

$app['conversation_service'] = function () {
    // return new Service();
};

$app->get('/init', function() use ($app, $log, $hook_url) {
    $log->info($hook_url);
    $service = $app['telegram_service'];
    $service->setWebhook($hook_url);
    return $app->json(array('init' => 'telegram'));
});

$app->get('/', function() use ($app, $log) {
    $log->info('Hello!');
    return $app->json(array('hello' => 'world'));
});

$app->post('/webhook', function(Symfony\Component\HttpFoundation\Request $request) use ($app, $log) {
    $data = json_decode($request->getContent(), true);
    $log->info($request->getContent());

    $app['telegram_service']->handle();
    $response = [
        'chat_id' => $data['message']['chat']['id'],
        'text' => $data['message']['text']
    ];
    $result = Longman\TelegramBot\Request::sendMessage($response);
    $log->info($result);

    return $app->json(array('webhook' => 'ok'));
});

$app->run();

?>