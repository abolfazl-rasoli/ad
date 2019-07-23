<?php


namespace App\Repositories;


use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class SourceRepo
{
    /**
     * @param int $customerId
     * @param array $sourceNames
     * @return Builder
     */
    public static function getValidSource(int $customerId , array $sourceNames=[]): Builder{
        $sources= DB::table('sources')
            ->join('contracts', 'sources.id', '=', 'contracts.source_id')
            ->where('sources.enable' , 1)
            ->where('contracts.user_id' , $customerId)
            ->where(function (Builder $q){
                $q ->where('contracts.since', '<=', Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now('Asia/Tehran')))
                    ->orWhereNull('contracts.since');
            })->where(function (Builder $q) {
                $q->where('contracts.till', '>=', Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now('Asia/Tehran')))
                    ->orWhereNull('contracts.till');
            });

        if(!empty($sourceNames)){
            $sources->whereIn('sources.name' , $sourceNames);
        }

        return $sources->select('*');
    }
}
