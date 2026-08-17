<?php

namespace Echoyl\Sa\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deadmin:table
                    {group? : Table group to install (pca/wechat/workflow/web)}
                    {--miniprogram : Install wechat miniprogram tables}
                    {--offiaccount : Install wechat offiaccount tables}
                    {--pay : Install wechat pay tables}
                    {--all : Install all wechat sub-tables (miniprogram + offiaccount + pay + session)}
                    {--force : Drop tables and re-import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install database tables (pca/wechat/workflow/web)';

    /**
     * Available table groups.
     *
     * @var array<string, string>
     */
    protected array $groups = [
        'pca' => 'Province/City/Area data',
        'wechat' => 'WeChat tables (miniprogram/offiaccount/pay/session)',
        'workflow' => 'Workflow tables (log/node/workflow)',
        'web' => 'Web tables (web_menu)',
    ];

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $group = $this->argument('group');

        if ($group === '' || ! isset($this->groups[$group])) {
            $this->showHelp();

            return 0;
        }

        if ($group === 'wechat') {
            return $this->installWechat();
        }

        return $this->installSql($group);
    }

    /**
     * Install a single SQL file.
     */
    protected function installSql(string $group): int
    {
        $this->components->info("Installing {$group}...");

        $sqlFile = __DIR__.'/../../../database/schema/'.$group.'.sql';

        if (! file_exists($sqlFile)) {
            $this->components->error("SQL file not found: {$group}.sql");

            return 1;
        }

        $sql = file_get_contents($sqlFile);

        DB::unprepared($sql);

        $this->components->info('Done.');

        return 0;
    }

    /**
     * Install wechat sub-tables.
     */
    protected function installWechat(): int
    {
        $groups = $this->resolveWechatGroups();

        if (empty($groups)) {
            $this->components->warn('No wechat module specified. Use --miniprogram, --offiaccount, --pay, or --all.');
            $this->newLine();
            $this->components->info('Available wechat options:');
            $this->line('  --miniprogram   Install miniprogram tables');
            $this->line('  --offiaccount   Install offiaccount tables');
            $this->line('  --pay           Install pay tables');
            $this->line('  --all           Install all wechat tables');
            $this->line('  --force         Drop tables and re-import');

            return 0;
        }

        $sqlDir = __DIR__.'/../../../database/schema/wechat';

        foreach ($groups as $group) {
            $sqlFile = $sqlDir.'/'.$group.'.sql';

            if (! file_exists($sqlFile)) {
                $this->components->error("SQL file not found: wechat/{$group}.sql");

                continue;
            }

            $this->components->info("Installing wechat/{$group}...");

            $sql = file_get_contents($sqlFile);

            DB::unprepared($sql);

            $this->components->info('  Done.');
        }

        $this->newLine();
        $this->components->info('All specified wechat tables have been installed.');

        return 0;
    }

    /**
     * Resolve which wechat sub-groups to install.
     */
    protected function resolveWechatGroups(): array
    {
        $groups = ['session'];

        if ($this->option('all')) {
            return array_merge($groups, ['miniprogram', 'offiaccount', 'pay']);
        }

        if ($this->option('miniprogram')) {
            $groups[] = 'miniprogram';
        }
        if ($this->option('offiaccount')) {
            $groups[] = 'offiaccount';
        }
        if ($this->option('pay')) {
            $groups[] = 'pay';
        }

        return $groups;
    }

    /**
     * Show help information.
     */
    protected function showHelp(): void
    {
        $this->components->info('Available table groups:');
        $this->newLine();

        foreach ($this->groups as $name => $desc) {
            $this->line("  <info>{$name}</info>  {$desc}");
        }

        $this->newLine();
        $this->components->info('Usage:');
        $this->line('  php artisan deadmin:table <group> [options]');
        $this->newLine();
        $this->components->info('Examples:');
        $this->line('  php artisan deadmin:table pca');
        $this->line('  php artisan deadmin:table wechat --all');
        $this->line('  php artisan deadmin:table wechat --miniprogram');
        $this->line('  php artisan deadmin:table workflow');
        $this->line('  php artisan deadmin:table web');
    }
}
