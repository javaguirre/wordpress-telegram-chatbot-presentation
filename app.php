<?php

require __DIR__ . '/vendor/autoload.php';

use Monolog\Logger;

use OdbBot\TelegramService;
use OdbBot\ConversationService;
use OdbBot\ApiAiService;
use OdbBot\WordpressApiService;

// Environment variables we need
define("BASE_WEBHOOK_URL", getenv('BASE_WEBHOOK_URL'));
define("TELEGRAM_BOT_KEY", getenv('TELEGRAM_BOT_KEY'));
define("TELEGRAM_BOT_NAME", getenv('TELEGRAM_BOT_NAME'));
define('WEBHOOK_URL', BASE_WEBHOOK_URL . '/webhook');
define('WORDPRESS_API_URL', getenv('WORDPRESS_API_URL') . '/wp-json/wp/v2/');
define('APIAI_TOKEN', getenv('APIAI_TOKEN'));
define('WP_USERNAME', getenv('WP_USERNAME'));
define('WP_PASSWORD', getenv('WP_PASSWORD'));
// Application
$app = new Silex\Application();

// Logs and debug
$log = new Logger('name');
$app['debug'] = true;
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => 'php://stderr'
));

// Services
$app['telegram_service'] = function () use ($log) {
    return new TelegramService(TELEGRAM_BOT_KEY, TELEGRAM_BOT_NAME);
};
$app['apiai_service'] = function () use ($log) {
    return new ApiAiService(APIAI_TOKEN);
};
$app['wordpress_service'] = function () use ($log) {
    return new WordpressApiService(WORDPRESS_API_URL, WP_USERNAME, WP_PASSWORD);
};
$app['conversation_service'] = function () use ($log) {
    return new ConversationService();
};

$app->get('/init', function() use ($app, $log) {
    $service = $app['telegram_service'];
    $service->setWebhook(WEBHOOK_URL);
    return $app->json(array('init' => 'telegram'));
});

$app->post('/webhook',
           function(Symfony\Component\HttpFoundation\Request $request)
           use ($app, $log) {

    $data = json_decode($request->getContent(), true);
    $app['telegram_service']->handle();

    $telegramResponse = $app['telegram_service']->getBasicData($data);
    $apiaiResponse = $app['apiai_service']->send($telegramResponse['text']);
    $response = $app['conversation_service']->process(
        $app, $telegramResponse['chat_id'], $apiaiResponse);

    $result = Longman\TelegramBot\Request::sendMessage($response);

    return $app->json(array('webhook' => 'ok'));
});

$app->run();

?>