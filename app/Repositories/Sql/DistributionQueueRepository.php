<?php

namespace App\Repositories\Sql;

use App\Models\Sql\DistributionQueue;
use App\Repositories\BaseSqlRepository;
use Illuminate\Support\Facades\DB;

class DistributionQueueRepository extends BaseSqlRepository
{
    public function getModel()
    {
        return DistributionQueue::class;
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

    public function search($limit = 10)
    {
        $data = DistributionQueue::where([
            DistributionQueue::COL_DISTRIBUTION_QUEUE_STATUS => DistributionQueue::DISTRIBUTION_QUEUE_STATUS_INIT
        ])
        ->orderBy(DistributionQueue::COL_DISTRIBUTION_QUEUE_CREATED_AT, 'ASC')
        ->get()
        ->groupBy(DistributionQueue::COL_DISTRIBUTION_QUEUE_REQUEST)
        ->map(function($value) use ($limit) {
            return $value->take($limit);
        });

        return $data;
    }

    public function countByStatus($status)
    {
        $data = DistributionQueue::where([
            DistributionQueue::COL_DISTRIBUTION_QUEUE_STATUS => $status
        ])
        ->get();

        return $data->count();
    }
}
