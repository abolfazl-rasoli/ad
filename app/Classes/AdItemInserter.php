<?php


namespace App\Classes;


use App\Repositories\Repo;
use App\Traits\adTrait;
use Carbon\Carbon;
use Illuminate\Support\Str;
use stdClass;

class AdItemInserter
{
    use adTrait;

    /**
     * @param stdClass $source
     * @param $item
     * @param AdPicTransferrer $adPicTransferrer
     * @return bool
     */
    public function storeItem(stdClass $source, $item , AdPicTransferrer $adPicTransferrer): bool
    {
        if(!$this->isValidItem($item))
        {
            return false;
        }

        [$storeResult, $picPath] = $adPicTransferrer->storeAdPic(optional($item)->image);
        if ($storeResult) {
            [$picTransfer, $picUrl] = $adPicTransferrer->transferAdPicToCDN($picPath);
            if ($picTransfer) {
                $item->image = $picUrl;
            }
        }

        if ($this->hasBeenInserted($this->makeAdForeignId($source->id, optional($item)->id , optional($item)->type))) {
            $this->updateAdRecord($item);
            return true;
        }

        $this->insertAdRecord($source, $item);
        return true;
    }

    /**
     * @param stdClass $source
     * @param $item
     */
    private function insertAdRecord(stdClass $source, $item): void
    {
        Repo::insertRecord('ads', [
            'UUID'  => Str::uuid()->toString() ,
            'source_id' => $source->id,
            'foreign_id' => $this->makeAdForeignId($source->id , optional($item)->id , optional($item)->type),
            'type' => optional($item)->type,
            'name' => optional($item)->name,
            'image' => optional($item)->image,
            'link' => optional($item)->link,
            'enable' => 1,
            'created_at' => Carbon::now(),
        ]);
    }

    private function updateAdRecord($item)
    {
        Repo::updateRecord('ads', [
            'type' => optional($item)->type,
            'name' => optional($item)->name,
            'image' => optional($item)->image,
            'link' => optional($item)->link,
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * @param $item
     * @return bool
     */
    private function isValidItem($item):bool
    {
        return isset($item->id) && isset($item->name) && isset($item->link) && isset($item->image);
    }

    private function hasBeenInserted(string $adId):bool
    {
        $ad = Repo::getRecords('ads', ['id'] ,['foreign_id'=>$adId])->first();
        return (isset($ad))?true:false;
    }
}
