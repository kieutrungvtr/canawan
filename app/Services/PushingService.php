<?php

namespace App\Services;

use App\Jobs\PullDesignJob;
use App\Models\Sql\DistributionQueue;
use App\Repositories\Sql\DistributionQueueRepository;
use Carbon\Carbon;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class PushingService
{
    private $distributionQueueRepository;

    private $quota = 4;

    /**
     * Flag support process exception case: break internet, sever die,...
     */
    private $backLogFlag = false;

    public function backlogFlag($value)
    {
        $this->backLogFlag = $value;
    }

    private function queueName($jobName)
    {
        $queueName = substr(trim($jobName), 0, -3);
        return Str::snake($queueName);
    }

    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        $this->distributionQueueRepository = new DistributionQueueRepository();
    }

    public function init()
    {
        
    }

    public function pre($id)
    {
        $this->distributionQueueRepository->update(
            $id,
            [
                DistributionQueue::COL_DISTRIBUTION_QUEUE_STATUS => DistributionQueue::DISTRIBUTION_QUEUE_STATUS_PUSHED
            ]
        ); 
         
    }

    public function post($id, $status)
    {
        $this->distributionQueueRepository->update(
            $id,
            [
                DistributionQueue::COL_DISTRIBUTION_QUEUE_STATUS => $status
            ]
        ); 
    }

    public function mix($data)
    {
        $tmp = [];
        $mixData = [];
        foreach ($data as $key => $value) {
            $uuid = $value->data_pushing_uuid;
            if (!in_array($uuid , $tmp)) {
                array_push($mixData, $value);
                array_push($tmp, $uuid);
            }
        }
        return $mixData;
    }

    /**
     * Note: Queue name will be base on job name. Ex: Job name is PullDesignJob => Queue name: pull_design.
     */
    public function process($jobName, $batch = 10, $mixFlag = false)
    {
        $itemPushed = $this->distributionQueueRepository->countByStatus(DistributionQueue::DISTRIBUTION_QUEUE_STATUS_PUSHED);
        if ($itemPushed >= $this->quota) {
            print_r("Over quota: $this->quota");
            echo PHP_EOL;
            return;
        }
        $dataGroupById = $this->distributionQueueRepository->search($batch);
        $rawData = Arr::flatten($dataGroupById->toArray(), 1);
        $mixFlag ? $distributionQueueData = $this->mix($rawData) : $distributionQueueData = $rawData;
        foreach ($distributionQueueData as $key => $value) {
            $countRequest = $key + 1;
            $uuid = $value[DistributionQueue::COL_DISTRIBUTION_QUEUE_REQUEST];  
            $payload = json_decode($value[DistributionQueue::COL_DISTRIBUTION_QUEUE_PAYLOAD], true);
            $designName = $payload['name'];
            $url = $payload['url'];
            echo PHP_EOL;
            print_r("Request $countRequest : $uuid >> $designName >> Url: $url");
            echo PHP_EOL;
            $jobInstance = "\\App\\Jobs\\$jobName";
            $jobs = (new $jobInstance($value))->onQueue($this->queueName($jobName))->delay(Carbon::now()->addSeconds(0));
            dispatch($jobs);
            $this->pre($value[DistributionQueue::COL_DISTRIBUTION_QUEUE_ID]);
        }
    }
}
