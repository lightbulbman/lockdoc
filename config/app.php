<?php

use DI\Container;
use Dotenv\Dotenv;
use Slim\Views\Twig;
use Slim\Flash\Messages;
use Slim\Factory\AppFactory;
use Slim\Views\TwigMiddleware;

require_once __DIR__ . '/../vendor/autoload.php';

//Start the session
session_start();

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();


// Create Container using PHP-DI
$container = new Container();
AppFactory::setContainer($container);

$container->set('settings', function() {
    return [
        'iv'                    =>  openssl_random_pseudo_bytes(16),
        'storage'               => __DIR__.'/../storage',
        'archive_pw'            => $_ENV['ARCHIVE_PW'],
        'archive_name'          => $_ENV['ARCHIVE_NAME'],
        'cipher_length'         => 16,
        'CRYPT_AES_MODE_CBC'    => 2,
    ];
});

$container->set('storage_directory', __DIR__ . '/storage');

// Register provider
$container->set('flash', function () {
    return new Messages();
});

// Set view in Container
$container->set('view', function() {
    return Twig::create(__DIR__ . '/../resources/views', [
        'cache' => false,
    ]);
});

// Create App
$app = AppFactory::create();

// Add Twig-View Middleware
$app->add(TwigMiddleware::createFromContainer($app));


//Bring in routes file
require_once __DIR__ . '/../routes/web.php';