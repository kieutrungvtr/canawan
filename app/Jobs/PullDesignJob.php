<?php

namespace App\Jobs;

use App\Models\Sql\DistributionQueue;
use App\Models\Users;
use App\Repositories\Sql\DistributionQueueRepository;
use App\Services\PushingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class PullDesignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $data;

    /**
     * The Distribution Queue instance.
     *
     * @var use App\Models\Sql\DistributionQueue;
     */
    public $distributionQueue;

 
    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 3600;
 
    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {  
        return 'distribution_queue_id';
    }

    /**
     * Create a new job instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    // public function middleware()
    // {
    //     return [(new WithoutOverlapping('distribution_queue_id'))->dontRelease()];
    // }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //$this->distributionQueue = new DistributionQueue();
        $pushingService = new PushingService();
        $uuid = $this->data[DistributionQueue::COL_DISTRIBUTION_QUEUE_REQUEST];
        $id = $this->data[DistributionQueue::COL_DISTRIBUTION_QUEUE_ID];
        $payload = json_decode($this->data[DistributionQueue::COL_DISTRIBUTION_QUEUE_PAYLOAD], true);
        $designName = $payload['name'];
        $url = $payload['url'];
        sleep(10);
        $pushingService->post($id, DistributionQueue::DISTRIBUTION_QUEUE_STATUS_FINISH);
        //var_dump($this->distributionQueue);
        //var_dump($this->distributionQueue->distribution_queue_id);
        print_r("\n");
        print_r(">>>> ID: $id - UUID: $uuid - Design: $designName");
        print_r("\n");
        print_r("\n");
    }
}
