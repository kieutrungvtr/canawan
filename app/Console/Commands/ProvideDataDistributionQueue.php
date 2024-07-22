<?php

namespace App\Console\Commands;

use App\Http\Requests\DistributionRequest;
use App\Models\Sql\DesignImportRequests;
use App\Models\Sql\Distributions;
use App\Services\PushingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

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
        // $request = new DistributionRequest();
        // $request->merge([
        //     'username' => 'test',
        //     //'email' => 'test',
        //     'password' => '12345678',
        // ]);

        // // Validate the request
        // $validator = Validator::make($request->all(), $request->rules());
        // //$validatedData = $request->validated();
        // var_dump($validator->fails());die;




        $designRequest = DesignImportRequests::where(
            [
                DesignImportRequests::COL_STATUS => 'reading'
            ]
        )->take(10)->get();
        $data = new DistributionRequest();
        foreach ($designRequest as $key => $value) {
            $requestId = $value->{DesignImportRequests::COL_ID};
            $payload = null;
            $jobName = "PullDesignJob";
            $tmp['distribution_request'][] = [
                Distributions::COL_DISTRIBUTION_REQUEST_ID => $requestId,
                Distributions::COL_DISTRIBUTION_PAYLOAD => $payload ?? '{}',
                Distributions::COL_DISTRIBUTION_JOB_NAME => $jobName,
            ];
        }
        $data = $data->merge($tmp);
        $pushingService = new PushingService();
        $res = $pushingService->init($data);
        var_dump($res);
    }
}
