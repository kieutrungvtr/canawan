<?php

namespace App\Console\Commands;

use App\Services\PushingService;
use Illuminate\Console\Command;

class CleanUpPushingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-up-pushing-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $batch = intval(config('distribution.batch'));
        $pushingService = new PushingService();
        $pushingService->backlogFlag(true);
        $pushingService->process("PullDesignJob", $batch);
    }
}
