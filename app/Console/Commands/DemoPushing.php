<?php

namespace App\Console\Commands;

use App\Services\PushingService;
use Illuminate\Console\Command;

class DemoPushing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:demo-pushing';

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
        $batch = 2;
        $pushingService = new PushingService();
        $pushingService->process('PullDesignJob', $batch);
    }
}
