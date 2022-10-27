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

    public function test()
    {
//        $article = new Article();
//        $article->title = '第二条测试，测试文章标题';
//        $article->content = '第二条测试，测试文章内容';
//        $article->save();

        $res = Article::search('我是中国人，测试文章标题')->get()->toArray();
        print_r($res);

        return $this->success('');

    }
}
