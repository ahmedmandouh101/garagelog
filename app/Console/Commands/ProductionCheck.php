<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProductionCheck extends Command
{
    protected $signature = 'app:production-check';

    protected $description = 'Read-only deployment configuration and database readiness checks; never prints secrets';

    public function handle(): int
    {
        $checks = [
            'Production environment' => app()->isProduction(),
            'Debug disabled' => ! config('app.debug'),
            'Application key present' => filled(config('app.key')),
            'HTTPS canonical URL' => str_starts_with(config('app.url'), 'https://') && ! str_contains(config('app.url'), 'example.'),
            'Encrypted secure cookies' => config('session.secure') && config('session.encrypt'),
            'Persistent cache' => in_array(config('cache.default'), ['database', 'redis']),
            'Persistent queue' => in_array(config('queue.default'), ['database', 'redis']),
            'Real mail transport' => ! in_array(config('mail.default'), ['log', 'array', null]),
            'Sender configured' => filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL) && ! str_contains(config('mail.from.address'), 'example.'),
            'Database is MySQL' => config('database.default') === 'mysql',
            'Storage writable' => is_writable(storage_path()) && is_writable(base_path('bootstrap/cache')),
            'Debug asset server absent' => ! file_exists(public_path('hot')),
        ];
        try {
            DB::select('SELECT 1');
            $checks['Database reachable'] = true;
            $migrator = app('migrator');
            $checks['Migrations applied'] = $migrator->repositoryExists() && ! array_diff(array_keys($migrator->getMigrationFiles(database_path('migrations'))), $migrator->getRepository()->getRan());
            $checks['No demo identities'] = ! User::where('email', 'admin@garagelog.com')->orWhere('email', 'like', '%@garagelog.example.test')->exists();
            $checks['Verified administrator exists'] = User::where('role', 'admin')->whereNotNull('email_verified_at')->exists();
        } catch (\Throwable $exception) {
            $checks['Database/migration checks'] = false;
        }
        foreach ($checks as $label => $passed) {
            $this->line(($passed ? 'PASS ' : 'FAIL ').$label);
        }
        $this->warn('This checks local configuration only. It does not certify SMTP delivery, TLS, backups, scheduler or monitoring.');

        return in_array(false, $checks, true) ? self::FAILURE : self::SUCCESS;
    }
}
