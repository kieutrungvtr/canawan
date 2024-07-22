<?php

namespace App\Repositories\Sql;

use App\Models\Sql\Distributions;
use App\Models\Sql\DistributionStates;
use App\Repositories\BaseSqlRepository;
use Illuminate\Support\Facades\DB;

class DistributionRepository extends BaseSqlRepository
{
    public function getModel()
    {
        return Distributions::class;
    }

    public function searchRawQuery($limit = 10, $backlog = false)
    {
        $backlog ? $status = "pushed" : $status = "init";
        $data = DB::select("
            SELECT x.*
            FROM canawan_design.data_pushing x 
            JOIN canawan_design.data_pushing y 
                ON y.data_pushing_id >= x.data_pushing_id 
                AND y.data_pushing_uuid = x.data_pushing_uuid 
            WHERE x.data_pushing_priority <= y.data_pushing_priority
                  AND y.data_pushing_status in ('$status')
                  -- AND x.data_pushing_created_at <= y.data_pushing_created_at
            GROUP BY x.data_pushing_id
            HAVING COUNT(*) <= $limit   
            ORDER BY x.data_pushing_created_at ASC;
        ");

        return $data;
    }

    public function search($jobName, $requestId = null, $limit = 10)
    {
        $arrStatus = [
            DistributionStates::DISTRIBUTION_STATES_COMPLETED,
            DistributionStates::DISTRIBUTION_STATES_PUSHED
        ];
        $where = [
            Distributions::COL_DISTRIBUTION_JOB_NAME => $jobName
        ];
        if ($requestId) {
            $where[Distributions::COL_DISTRIBUTION_REQUEST_ID] = $requestId;
        }
        $data = Distributions::whereDoesntHave('states', function($query) use ($arrStatus) {
            $query->whereIn(DistributionStates::COL_DISTRIBUTION_STATE_VALUE, $arrStatus);
        })
        ->where($where)
        ->orderBy(Distributions::COL_DISTRIBUTION_CREATED_AT, 'ASC')
        ->get()
        ->groupBy(Distributions::COL_DISTRIBUTION_REQUEST_ID)
        ->map(function($value) use ($limit) {
            return $value->take($limit);
        });

        return $data;
    }

    public function searchBackLog($jobName, $requestId = null, $limit = 10)
    {
        $arrStatus = [
            DistributionStates::DISTRIBUTION_STATES_COMPLETED,
            DistributionStates::DISTRIBUTION_STATES_PUSHED
        ];
        $where = [
            Distributions::COL_DISTRIBUTION_JOB_NAME => $jobName
        ];
        if ($requestId) {
            $where[Distributions::COL_DISTRIBUTION_REQUEST_ID] = $requestId;
        }
        $data = Distributions::whereDoesntHave('states', function($query) use ($arrStatus) {
            $query->whereIn(DistributionStates::COL_DISTRIBUTION_STATE_VALUE, $arrStatus);
        })
        ->where($where)
        ->orderBy(Distributions::COL_DISTRIBUTION_CREATED_AT, 'ASC')
        ->get()
        ->groupBy(Distributions::COL_DISTRIBUTION_REQUEST_ID)
        ->map(function($value) use ($limit) {
            return $value->take($limit);
        });

        return $data;
    }

    public function countByStatus($status)
    {
        $data = Distributions::join(
            DistributionStates::TABLE_NAME,
            Distributions::COL_DISTRIBUTION_ID,
            DistributionStates::COL_FK_DISTRIBUTION_ID
        )
        ->where(DistributionStates::COL_DISTRIBUTION_STATE_VALUE, $status)
        ->get();

        return $data->count();
    }

    public function initDistributionData($data)
    {
        array_walk($data, function (&$subArray) {
            $subArray[Distributions::COL_DISTRIBUTION_CREATED_AT] = now();
        });
        return Distributions::insert($data);
    }
}
