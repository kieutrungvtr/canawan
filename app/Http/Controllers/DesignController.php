<?php

namespace App\Http\Controllers;

use App\Jobs\PullDesignJob;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;

class DesignController extends Controller
{
    public function sendEmail()
    {
        //$emailJob = (new PullDesignJob())->delay(Carbon::now()->addSeconds(3));
        //dispatch($emailJob);

        echo 'email sent';
    }
}
