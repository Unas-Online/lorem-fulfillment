<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Controller\FulfillmentController;
use App\Controller\SettingsController;
use App\Controller\UnasAppController;
use App\Middleware\AddApiClient;
use App\Middleware\SessionMiddleware;
use App\Middleware\VerifyUnasAppRequest;
use DI\Container;
use Dotenv\Dotenv;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use UnasOnline\UnasConnect\AppClient;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$container = new Container();
AppFactory::setContainer($container);

$app = AppFactory::create();
$app->add(new SessionMiddleware($container));
$container->set('app', $app);
$container->set('app-root', realpath(__DIR__ . '/..'));

$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$twig = Twig::create(__DIR__ . '/../templates', ['cache' => false]);
$app->add(TwigMiddleware::create($app, $twig));

$container->set('unas-app', appClientFromEnv());

$app->group('/unas', function (RouteCollectorProxy $group) {
    $group->get('/start', [UnasAppController::class, 'start'])->add(AddApiClient::class);
    $group->post('/install', [UnasAppController::class, 'install']);
    $group->post('/uninstall', [UnasAppController::class, 'uninstall']);
})->add(VerifyUnasAppRequest::class);

$app->get('/', [FulfillmentController::class, 'index'])->add(AddApiClient::class)->setName('index');
$app->post('/', [FulfillmentController::class, 'loadOrders'])->add(AddApiClient::class)->setName('index');
$app->post('/simulate', [FulfillmentController::class, 'simulate'])->add(AddApiClient::class)->setName('simulate');
$app->get('/settings', [SettingsController::class, 'settings'])->add(AddApiClient::class)->setName('settings');
$app->post('/settings', [SettingsController::class, 'saveSettings'])->add(AddApiClient::class)->setName('settings-save');
$app->post('/webhook/orderstatus', [FulfillmentController::class, 'handleWebhook']);

$app->run();

function appClientFromEnv(): AppClient
{
    if (!array_key_exists('UNAS_APP_ID', $_ENV)) {
        throw new \Exception('no app id provided');
    }
    $appId = $_ENV['UNAS_APP_ID'];

    if (!array_key_exists('UNAS_APP_URL', $_ENV)) {
        throw new \Exception('no app url provided');
    }
    $appUrl = $_ENV['UNAS_APP_URL'];

    if (!array_key_exists('UNAS_APP_SECRET', $_ENV)) {
        throw new \Exception('no app secret provided');
    }
    $appSecret = $_ENV['UNAS_APP_SECRET'];

    return new AppClient($appId, $appUrl, $appSecret);
}
