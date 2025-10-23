<?php

$container->set('db_settings', function () {
    return (object) [
        "DB_NAME" => $_ENV['DB_DATABASE'],
        "DB_PASS" => $_ENV['DB_PASSWORD'],
        "DB_CHAR" => $_ENV['DB_CHARSET'],
        "DB_HOST" =>  $_ENV['DB_HOSTNAME'],
        "DB_USER" => $_ENV['DB_USERNAME']
    ];
});