<?php

namespace App\Console\Commands;

use App\Libs\ELogger;
use App\Services\PushingService;
use Illuminate\Console\Command;

class DistributionPushing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'distribution:pushing {--request_id=} {--sync=}';

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
        //ELogger::info("messege", ["context" => "test"]);
        $requestId = $this->option('request_id') ?? null;
        $sync = $this->option('sync') ?? null;
        $batch = intval(config('distribution.batch'));
        $pushingService = new PushingService();
        if ($requestId) {
            $pushingService->optionRequestId($requestId);
        }
        if ($sync) {
            $pushingService->optionSync($sync);
        }
        $res = $pushingService->process('PullDesignJob', $batch);
        print_r($res->getContent());
    }

}
