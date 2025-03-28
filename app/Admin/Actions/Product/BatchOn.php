<?php

namespace App\Admin\Actions\Product;

use App\Models\TiktokProduct;
use Encore\Admin\Actions\BatchAction;
use Encore\Admin\Facades\Admin;
use Illuminate\Database\Eloquent\Collection;

class BatchOn extends BatchAction
{
    public $name = '批量 - 上架';

    public function handle(Collection $collection)
    {
        if (!Admin::user()->can('product.manage-all')) {
            return $this->response()->error('你无执行该操作的权限')->refresh();
        }
        $ids = $collection->pluck('id')->toArray();
        $num = TiktokProduct::whereIn('id', $ids)->update(['ud' => 1]);

        return $this->response()->success(sprintf("成功批量上架 %s 个产品", $num))->refresh();
    }

}
