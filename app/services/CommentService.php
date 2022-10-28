<?php

namespace App\services;

use App\Models\Comment;
use App\Models\Reply;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class CommentService
{
    public function add($param)
    {
        DB::transaction(function() use ($param) {
            Comment::create($param);
            ArticleService::increment($param['article_id'], 'comment_count');
        });

        return ['success' => true];
    }

    public function reply($param)
    {
        DB::transaction(function() use ($param) {
            Reply::create($param);
            ArticleService::increment($param['article_id'], 'comment_count');
            Comment::where('id', $param['comment_id'])->increment('comment_count');
        });

        return ['success' => true];
    }

    public function lists($param)
    {
        $order_type = isset($param['order_type']) ? $param['order_type'] : 'recommend';
        switch ($order_type) {
            case 'recommend':
                $order_field = 'comment_count';
                break;
            case 'new':
                $order_field = 'created_at';
                break;
            default:
                $order_field = 'comment_count';
        }

        $lists = Comment::query()
            ->where('article_id', $param['article_id'])
            ->orderByDesc($order_field)
            ->get();

        foreach ($lists as &$info) {
            if ($info['comment_count'] >= 1) {
                $reply_list = Reply::query()
                    ->where('comment_id', $info['id'])
                    ->orderBy('id')
                    ->limit(3)
                    ->get()
                    ->toArray();

                $info['reply'] = $reply_list;

            } else {
                $info['reply'] = [];
            }
        }

        return $lists;
    }

    //点赞/反对
    public function vote($param, $model)
    {
        $redis_key = sprintf('%s_vote_%:%s', $param['comment_type'], $param['type'], $param['id']);
        $vote_field = sprintf('vote_%s_count', $param['type']);
        $log = Redis::sismember($redis_key, $param['uid']);
        if ($param['status'] == 'on') {
            if (!$log) {
                Redis::sadd($redis_key, $param['uid']);
                $model->increment($vote_field);
            }
        } else {
            if ($log) {
                Redis::srem($redis_key, $param['uid']);
                $model->decrement($vote_field);
            }
        }

        $model->save();

        $num = $model->$vote_field;

        return $num;
    }

    public static function getModelByType($type, $id)
    {
        if ($type == 'comment') {
            $model = Comment::find($id);
        } else {
            $model = Reply::find($id);
        }
        return $model;
    }

    //判断被@用户是否存在
    public static function existsAtUid($comment_id, $at_uid)
    {
        return Reply::where('comment_id', $comment_id)
            ->where('uid', $at_uid)->exists();
    }

    //判断一级评论id是否存在
    public static function existsCommentId($article_id, $comment_id)
    {
        return Comment::where('id', $comment_id)
            ->where('article_id', $article_id)->exists();
    }

}
