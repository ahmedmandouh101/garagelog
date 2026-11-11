<?php

use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Process\Process;

// Creates and drops ONLY a new isolated test database. Never points tests at the application DB.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('local', 'testing')) {
    fwrite(STDERR, "Refusing integration tests outside local/testing.\n");
    exit(1);
}
$cfg = config('database.connections.mysql');
$name = 'garagelog_ci_'.bin2hex(random_bytes(8));
$created = false;
$exitCode = 1;
try {
    $pdo = new PDO('mysql:host='.$cfg['host'].';port='.$cfg['port'], $cfg['username'], $cfg['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    $process = new Process([PHP_BINARY, 'vendor/bin/pest', '--compact'], base_path(), [
        'APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => $name, 'DB_URL' => '',
        'DB_HOST' => $cfg['host'], 'DB_PORT' => (string) $cfg['port'], 'DB_USERNAME' => $cfg['username'], 'DB_PASSWORD' => $cfg['password'],
    ]);
    $process->setTimeout(180);
    $exitCode = $process->run(fn ($type, $buffer) => print ($buffer));
} catch (Throwable $e) {
    fwrite(STDERR, "Isolated MySQL integration run failed; check database access and server availability.\n");
} finally {
    if ($created && preg_match('/^garagelog_ci_[a-f0-9]{16}$/', $name)) {
        $pdo->exec('DROP DATABASE `'.$name.'`');
        fwrite(STDOUT, "Isolated test database removed.\n");
    }
}
exit($exitCode);
