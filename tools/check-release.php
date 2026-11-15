<?php

// No deployment, email sending, credential lookup, or remote API writes.
$root = dirname(__DIR__);
if (! is_file($root.'/vendor/autoload.php')) {
    fwrite(STDERR, "Install development dependencies with composer install before checking.\n");
    exit(1);
}
require $root.'/vendor/autoload.php';

use Symfony\Component\Process\Process;

$prePush = in_array('--pre-push', $argv, true);
$mysql = in_array('--mysql', $argv, true);
$capture = static function (array $command) use ($root): string {
    $process = new Process($command, $root);
    $process->mustRun();

    return trim($process->getOutput());
};
try {
    $revision = $capture(['git', 'rev-parse', 'HEAD']);
    if ($prePush) {
        $hasUpdates = false;
        while (($line = fgets(STDIN)) !== false) {
            $fields = preg_split('/\s+/', trim($line));
            if (count($fields) !== 4) {
                throw new RuntimeException('Invalid pre-push input.');
            }
            if (preg_match('/^0+$/', $fields[1])) {
                continue; // A deletion has no application tree to test.
            }
            $hasUpdates = true;
            if ($capture(['git', 'rev-parse', $fields[1].'^{commit}']) !== $revision) {
                throw new RuntimeException('Check out the commit being pushed first. This hook validates the current HEAD only.');
            }
        }
        if (! $hasUpdates) {
            exit(0);
        }
        if ($capture(['git', 'status', '--porcelain', '--untracked-files=normal']) !== '') {
            throw new RuntimeException('Commit or stash pending files before pushing, so the checked files match the pushed commit.');
        }
    }
    if (is_file($root.'/bootstrap/cache/config.php')) {
        throw new RuntimeException('Cached Laravel configuration found. Run php artisan config:clear in this development checkout first.');
    }
    $steps = [['PHP feature and security tests', [PHP_BINARY, 'vendor/bin/pest', '--compact']]];
    foreach (glob($root.'/public/*.js') as $file) {
        $steps[] = ['JavaScript syntax: '.basename($file), ['node', '--check', $file]];
    }
    $steps[] = ['Production asset build', ['node', 'node_modules/vite/bin/vite.js', 'build']];
    if ($mysql) {
        $steps[] = ['Isolated MySQL suite', [PHP_BINARY, 'tools/test-mysql.php']];
    }
    $report = ['revision' => $revision, 'started_at' => gmdate(DATE_ATOM), 'status' => 'running', 'steps' => []];
    $log = $root.'/storage/logs/local-checks.log';
    $save = static function () use (&$report, $log): void {
        if (! is_dir(dirname($log))) {
            mkdir(dirname($log), 0775, true);
        }
        file_put_contents($log, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL, LOCK_EX);
    };
    foreach ($steps as [$label, $command]) {
        fwrite(STDOUT, "\n[CHECK] {$label}\n");
        // Explicitly isolate PHP tests even when the shell contains application DB variables.
        $testEnv = $label === 'PHP feature and security tests' ? [
            'APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:',
            'DB_URL' => '', 'MAIL_MAILER' => 'array', 'CACHE_STORE' => 'array',
            'QUEUE_CONNECTION' => 'sync', 'SESSION_DRIVER' => 'array',
        ] : null;
        $process = new Process($command, $root, $testEnv);
        $process->setTimeout(240);
        $exitCode = $process->run(static fn ($type, $buffer) => print ($buffer));
        $report['steps'][] = ['name' => $label, 'exit_code' => $exitCode];
        if ($exitCode !== 0) {
            $report['status'] = 'failed';
            $report['finished_at'] = gmdate(DATE_ATOM);
            $save();
            fwrite(STDERR, "\nCHECK FAILED: {$label}. Push stopped. Details: storage/logs/local-checks.log\n");
            exit(1);
        }
    }
    if ($prePush && ($capture(['git', 'rev-parse', 'HEAD']) !== $revision || $capture(['git', 'status', '--porcelain', '--untracked-files=normal']) !== '')) {
        throw new RuntimeException('Files changed during verification. Commit or stash changes and push again.');
    }
    $report['status'] = 'passed';
    $report['finished_at'] = gmdate(DATE_ATOM);
    $save();
    fwrite(STDOUT, "\nALL CHECKS PASSED for ".substr($revision, 0, 8).".\n");
    exit(0);
} catch (Throwable $exception) {
    if (isset($report, $save)) {
        $report['status'] = 'failed';
        $report['finished_at'] = gmdate(DATE_ATOM);
        $save();
    }
    fwrite(STDERR, 'CHECK STOPPED: '.$exception->getMessage().PHP_EOL);
    exit(1);
}
