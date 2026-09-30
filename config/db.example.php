<?php

function get_db(): PDO
{
    $host = 'localhost';
    $port = '8889';
    $database = 'YOUR_DATABASE_NAME';
    $username = 'YOUR_DATABASE_USERNAME';
    $password = 'YOUR_DATABASE_PASSWORD';

    $dsn =
        "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE =>
        PDO::ERRMODE_EXCEPTION,

        PDO::ATTR_DEFAULT_FETCH_MODE =>
        PDO::FETCH_ASSOC,

        PDO::ATTR_EMULATE_PREPARES =>
        false,
    ];

    return new PDO(
        $dsn,
        $username,
        $password,
        $options
    );
}
