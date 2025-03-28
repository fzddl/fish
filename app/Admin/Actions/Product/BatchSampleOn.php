<?php

namespace App\Admin\Actions\Product;

use App\Models\TiktokProduct;
use Encore\Admin\Actions\BatchAction;
use Encore\Admin\Facades\Admin;
use Illuminate\Database\Eloquent\Collection;

class BatchSampleOn extends BatchAction
{
    public $name = '批量 - 可领样';

    public function handle(Collection $collection)
    {
        $ids = $collection->pluck('id')->toArray();
        $num = TiktokProduct::whereIn('id', $ids)->update(['is_samples' => 1]);

        return $this->response()->success(sprintf("批量-可领样 %s 个产品", $num))->refresh();
    }

}
