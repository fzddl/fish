<?php

namespace App\Http\Controllers\Api\HT;

use App\Http\Controllers\Controller;
use App\Http\Requests\ArticleRequest;
use App\services\ArticleService;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    protected $articleService;

    public function __construct(ArticleService $articleService)
    {
        $this->articleService = $articleService;
    }

    public function add(ArticleRequest $request)
    {
        $param = $request->all();
        if (empty($param['uid'])) { //调试
            $param['uid'] = $request->get('_user')->id;
        }

        $param['iso'] = $request->header('iso');
        $param['ip'] = $request->ip();
        $res = $this->articleService->add($param);

        if ($res['success']) {
            return $this->success('The article published successfully');
        } else {
            return $this->error($res['msg']);
        }
    }

    public function search(ArticleRequest $request)
    {
        $param = $request->all();
        if (empty($param['uid'])) { //调试
            $param['uid'] = $request->get('_user')->id;
        }

        $res = $this->articleService->search($param);

        return $this->success($res);

    }

    //收藏
    public function favorite(ArticleRequest $request)
    {
        $param = $request->all();
        if (empty($param['uid'])) { //调试
            $param['uid'] = $request->get('_user')->id;
        }

        if (!ArticleService::exists($param['article_id'])) {
            return $this->error('The article is not exist');
        }

        $res = $this->articleService->favorite($param);

        return $this->success($res);
    }

    //点赞/反对
    public function vote(ArticleRequest $request)
    {
        $param = $request->all();
        if (empty($param['uid'])) { //调试
            $param['uid'] = $request->get('_user')->id;
        }

        if (!ArticleService::exists($param['article_id'])) {
            return $this->error('The article is not exist');
        }

        $res = $this->articleService->vote($param);

        return $this->success($res);
    }

}
