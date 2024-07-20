<?php

namespace App\Console\Commands;

use App\Http\Requests\ProvideDataDistributionRequest;
use App\Models\Sql\DesignImportRequests;
use App\Models\Sql\DistributionQueue;
use App\Services\PushingService;
use Illuminate\Console\Command;

class ProvideDataDistributionQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:provide-data-distribution-queue';

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
        $designRequest = DesignImportRequests::where(
            [
                DesignImportRequests::COL_STATUS => 'reading'
            ]
        )->take(10)->get();
        $data = new ProvideDataDistributionRequest();
        foreach ($designRequest as $key => $value) {
            $requestId = $value->{DesignImportRequests::COL_ID};
            $payload = null;
            $jobName = "PullDesignJob";
            $tmp[] = [
                DistributionQueue::COL_DISTRIBUTION_QUEUE_REQUEST => $requestId,
                DistributionQueue::COL_DISTRIBUTION_QUEUE_PAYLOAD => $payload ?? '{}',
                DistributionQueue::COL_DISTRIBUTION_QUEUE_JOB_NAME => $jobName,
            ];
        }
        $data['distribution_queue'] = $tmp;
        $pushingService = new PushingService();
        $pushingService->init($data);
    }
}
