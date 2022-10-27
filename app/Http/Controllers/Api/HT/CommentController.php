<?php

namespace App\Http\Controllers\Api\HT;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommentRequest;
use App\services\CommentService;
use App\services\ArticleService;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    protected $commentService;

    public function __construct(CommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    //评论
    public function add(CommentRequest $request)
    {
        $param = $request->all();

        if (!ArticleService::exists($param['article_id'])) {
            return $this->error('The article is not exist');
        }

        if (empty($param['uid'])) { //调试
            $param['uid'] = $request->get('_user')->id;
        }

        $param['iso'] = $request->header('iso');
        $param['ip'] = $request->ip();
        $res = $this->commentService->add($param);

        if ($res['success']) {
            return $this->success('The comment published successfully');
        } else {
            return $this->error($res['msg']);
        }
    }

    //回复
    public function reply(CommentRequest $request)
    {
        $param = $request->all();

        if (!ArticleService::exists($param['article_id'])) {
            return $this->error('The article is not exist');
        }

        if (!empty($param['comment_id'])) {
            if (!CommentService::existsCommentId($param['article_id'], $param['comment_id'])) {
                return $this->error('The comment is not exist');
            }
        }

        if (!empty($param['at_uid'])) {
            if (!CommentService::existsAtUid($param['comment_id'], $param['at_uid'])) {
                return $this->error('The user is not exist');
            }
        }

        if (empty($param['uid'])) { //调试
            $param['uid'] = $request->get('_user')->id;
        }

        $param['iso'] = $request->header('iso');
        $param['ip'] = $request->ip();
        $res = $this->commentService->reply($param);

        if ($res['success']) {
            return $this->success('The reply published successfully');
        } else {
            return $this->error($res['msg']);
        }
    }

    public function lists(CommentRequest $request)
    {
        $param = $request->all();

        if (!ArticleService::exists($param['article_id'])) {
            return $this->error('The article is not exist');
        }

        $res = $this->commentService->lists($param);

        return $this->success($res);
    }
}
