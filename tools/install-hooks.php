<?php

$root = dirname(__DIR__);
require $root.'/vendor/autoload.php';

use Symfony\Component\Process\Process;

$read = new Process(['git', 'config', '--local', '--get', 'core.hooksPath'], $root);
$read->run();
$existing = trim($read->getOutput());
if ($existing !== '' && $existing !== '.githooks') {
    fwrite(STDERR, "A different core.hooksPath is already configured. Preserve it and integrate this check manually.\n");
    exit(1);
}
$install = new Process(['git', 'config', '--local', 'core.hooksPath', '.githooks'], $root);
$install->run();
if (! $install->isSuccessful()) {
    fwrite(STDERR, "Could not configure local Git hooks. Check repository write permissions.\n");
    exit(1);
}
if (PHP_OS_FAMILY !== 'Windows') {
    chmod($root.'/.githooks/pre-push', 0755);
}
fwrite(STDOUT, "Installed: every normal git push from this checkout runs local checks first.\n");
