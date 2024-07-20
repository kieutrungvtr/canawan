<?php

namespace App\Http\Requests;

use App\Models\Sql\DistributionQueue;
use Illuminate\Foundation\Http\FormRequest;

class ProvideDataDistributionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     */
    public function rules()
    {
        return [
            'distribution_queue' => 'required|array',
            sprintf(
                "%s.*.%s",
                DistributionQueue::TABLE_NAME,
                DistributionQueue::COL_DISTRIBUTION_QUEUE_REQUEST
            ) => 'required',
            sprintf(
                "%s.*.%s",
                DistributionQueue::TABLE_NAME,
                DistributionQueue::COL_DISTRIBUTION_QUEUE_JOB_NAME
            ) => 'required',
        ];
    }
}
