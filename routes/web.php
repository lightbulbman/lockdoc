<?php

use Lockdoc\Controllers\HomeController;

$app->get('/',          HomeController::class . ':index');
$app->post('/',         HomeController::class . ':secure');
$app->get('/archive',   HomeController::class . ':viewArchive');
$app->post('/archive',  HomeController::class . ':getFile');
