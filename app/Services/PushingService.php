<?php

namespace App\Services;

use App\Http\Requests\ProvideDataDistributionRequest;
use App\Models\Sql\DistributionQueue;
use App\Models\Sql\DistributionQueueStatus;
use App\Models\Sql\DistributionRequest;
use App\Models\Sql\Distributions;
use App\Models\Sql\DistributionStates;
use App\Queue\Jobs\RabbitMQJob;
use App\Repositories\Sql\DistributionQueueRepository;
use App\Repositories\Sql\DistributionRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

class PushingService
{
    private $distributionRepository;

    private $distributionStateRepository;

    private $quota = 4;

    /**
     * Flag support process exception case: break internet, sever die,...
     */
    private $backLogFlag = false;

    private $optionRequestId = null;

    private $optionSync = false;

    public function backlogFlag($value)
    {
        $this->backLogFlag = $value;
    }

    public function optionRequestId($value)
    {
        $this->optionRequestId = $value;
    }

    public function optionSync($value)
    {
        $this->optionSync = $value;
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
        $this->distributionRepository = new DistributionRepository();
        $this->distributionStateRepository = new DistributionStates();
    }

    /**
     * @param ProvideDataDistributionRequest $distributionData
     */
    public function init(ProvideDataDistributionRequest $distributionData)
    {
        $validatedData = $distributionData->validated();
        // foreach ($validatedData as $data) {

        // }
        
        return Distributions::insert($validatedData[Distributions::TABLE_NAME]);
    }

    public function pre($id)
    {
        $this->distributionStateRepository->create(
            [
                DistributionStates::COL_FK_DISTRIBUTION_ID => $id,
                DistributionStates::COL_DISTRIBUTION_STATE_VALUE => DistributionStates::DISTRIBUTION_STATES_PUSHED,
                DistributionStates::COL_DISTRIBUTION_STATE_CREATED_AT => now()
            ]
        ); 
         
    }

    public function post($id, $status, $log = null)
    {
        $this->distributionStateRepository->create(
            [
                DistributionStates::COL_FK_DISTRIBUTION_ID => $id,
                DistributionStates::COL_DISTRIBUTION_STATE_VALUE => $status,
                DistributionStates::COL_DISTRIBUTION_STATE_LOG => $log,
                DistributionStates::COL_DISTRIBUTION_STATE_CREATED_AT => now(),
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
        $itemPushed = $this->distributionRepository->countByStatus(
            DistributionStates::DISTRIBUTION_STATES_PUSHED
        );
        if ($itemPushed > $this->quota) {
            return Response::make("Over quota $this->quota", 406);
        }
        $dataGroupById = $this->distributionRepository->search($jobName, $this->optionRequestId, $batch);
        $rawData = Arr::flatten($dataGroupById->toArray(), 1);
        $mixFlag ? $distributionQueueData = $this->mix($rawData) : $distributionQueueData = $rawData;
        var_dump($distributionQueueData);
        try {
            foreach ($distributionQueueData as $key => $value) {
                $countRequest = $key + 1;
                $uuid = $value[Distributions::COL_DISTRIBUTION_REQUEST_ID];  
                $payload = json_decode($value[Distributions::COL_DISTRIBUTION_PAYLOAD], true);
                $designName = $payload['name'] ?? '';
                $url = $payload['url'] ?? '';
                echo PHP_EOL;
                print_r("Request $countRequest : $uuid >> $designName >> Url: $url");
                echo PHP_EOL;
                $jobInstance = "\\App\\Jobs\\$jobName";
                $jobs = new $jobInstance($value);
                if ($this->optionSync) {
                    dispatch_sync($jobs);
                } else {
                    //Queue::pushOn($this->queueName($jobName), $jobs);
                    $jobInstance::dispatch($value)->onQueue($this->queueName($jobName));
                }
                $this->pre($value[Distributions::COL_DISTRIBUTION_ID]);
            }
            return Response::make("$countRequest request pushed", 200);
        } catch (\Exception $e) {
            return Response::make($e->getMessage(), 503);
        }
    }
}
