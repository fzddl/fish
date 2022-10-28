<?php

namespace App\services;

use App\Models\Article;
use App\Models\ArticleFavorite;
use App\Models\ArticleTopic;
use App\Models\Friend;
use App\Models\Topic;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class ArticleService
{
    public function add($param)
    {
        $article_data = $param;
        $article_data['vote_up_count'] = 0;
        $article_data['vote_down_count'] = 0;

        $topic_ids = [];
        $topic_data = [];
        $new_topic = [];
        if (!empty($param['topic'])) {
            $topic_json = json_decode($param['topic'], true);
            foreach ($topic_json as $info) {
                if ($info['id'] == 0) {
                    if (!Topic::where('title', $info['title'])->exists()) {
                        $new_topic[] = [
                            'title' => $info['title'],
                            'uid' => $param['uid'],
                            'sort' => 0,
                            'pic' => '',
                            'article_count' => 0,
                            'visited_count' => 0,
                            'follow_count' => 0
                        ];
                    }
                } else {
                    array_push($topic_ids, $info['id']);
                }
            }

            if (!empty($topic_ids)) {
                $topic_data = Topic::query()->select('id', 'title')->whereIn('id', $topic_ids)->get()->toArray();
            }
        }

        DB::transaction(function () use ($article_data, $topic_data, $new_topic) {
            foreach ($new_topic as $topic) {
                $res = Topic::create($topic);
                array_push($topic_data, [
                    'id' => $res->id,
                    'title' => $res->title
                ]);
            }

            $topic_ids = Arr::pluck($topic_data, 'id');

            $article_data['topic_id_str'] = implode(',', $topic_ids);
            $article_data['topic_title_str'] = implode(',', Arr::pluck($topic_data, 'title'));

            $new_article = Article::create($article_data);
            $article_id = $new_article->id;

            $new_topic_relation = [];
            foreach ($topic_ids as $topic_id) {
                $new_topic_relation[] = [
                    'article_id' => $article_id,
                    'topic_id' => $topic_id
                ];
            }
            ArticleTopic::insert($new_topic_relation);
            Topic::query()->whereIn('id', $topic_ids)->increment('article_count');
        });

        return ['success' => true];
    }

    //收藏
    public function favorite($param)
    {
        $log = ArticleFavorite::query()->where('uid', $param['uid'])
            ->where('article_id', $param['article_id'])
            ->first();

        if ($param['status'] == 'on') {
            if (!$log) {
                DB::transaction(function() use($param) {
                    ArticleFavorite::create([
                        'article_id' => $param['article_id'],
                        'uid' => $param['uid']
                    ]);
                    Article::where('id', $param['article_id'])->increment('favorite_count');
                });
            }
        } else {
            if ($log) {
                DB::transaction(function() use($param, $log) {
                    $log->delete();
                    Article::where('id', $param['article_id'])->decrement('favorite_count');
                });
            }
        }

        self::searchable($param['article_id']);

        $num = Article::query()->where('id', $param['article_id'])->value('favorite_count');

        return $num;
    }

    //点赞/反对
    public function vote($param)
    {
        $redis_key = sprintf('article_vote_%:%s', $param['type'], $param['article_id']);
        $vote_field = sprintf('vote_%s_count', $param['type']);
        $log = Redis::sismember($redis_key, $param['uid']);
        if ($param['status'] == 'on') {
            if (!$log) {
                Redis::sadd($redis_key, $param['uid']);
                Article::where('id', $param['article_id'])->increment($vote_field);
            }
        } else {
            if ($log) {
                Redis::srem($redis_key, $param['uid']);
                Article::where('id', $param['article_id'])->decrement($vote_field);
            }
        }

        self::searchable($param['article_id']);

        $num = Article::query()->where('id', $param['article_id'])->value($vote_field);

        return $num;
    }

    public function search($param)
    {
        $es = Article::es();
        $friend_ids = Friend::query()->select('friend_id')->where('uid', $param['uid'])->get()->pluck('friend_id')->toArray();

        if ($param['type'] == 'follow') {
            $query = $this->getFollowQuery($param, $friend_ids);
        } elseif ($param['type'] == 'mine') {
            $query = $this->getMineQuery($param, $friend_ids);
        } else {
            $query = $this->getRecommendQuery($param, $friend_ids);
        }

        $start = $param['limit'] * ($param['page'] - 1);
        $rs = $es->searchDoc($query, $start, $param['limit'], ['order' => ['id' => 'desc']]);
        $array['total'] = $rs['total'];
        if (isset($rs['data'])) {
            foreach ($rs['data'] as $row) {
                $array['rs'][] = $row['content'];
            }
        }

        return $array;
    }

    //自己的列表
    private function getMineQuery($param, $friend_ids)
    {
        $query = [];
        $query['bool']['must'] = [];

        if (!empty($param['keyword'])) {
            $query['bool']['must'][] = [
                'multi_match' => [
                    'query' => $param['keyword'],
                    'fields' => [
                        'title',
                        'content',
                        'topic_title_str'
                    ]
                ]
            ];
        }

        $query['bool']['should'][] = [
            'bool' => [
                'must' => [
                    [
                        'term' => [
                            'uid' => ['value' => $param['uid']]
                        ]
                    ]
                ]
            ]
        ];

        return $query;
    }

    //关注的列表
    private function getFollowQuery($param, $friend_ids)
    {
        $query = [];
        $query['bool']['must'] = [];

        if (!empty($param['keyword'])) {
            $query['bool']['must'][] = [
                'multi_match' => [
                    'query' => $param['keyword'],
                    'fields' => [
                        'title',
                        'content',
                        'topic_title_str'
                    ]
                ]
            ];
        }

        $query['bool']['must'][] = [
            'bool' => [
                'must' => [
                    [
                        'terms' => [
                            'uid' => $friend_ids
                        ]
                    ],
                    [
                        'term' => [
                            'visible' => ['value' => 2]
                        ]
                    ]
                ]
            ]
        ];

        return $query;
    }

    //推荐列表
    private function getRecommendQuery($param, $friend_ids)
    {
        $query = [];
        $query['bool']['must'] = [];

        if (!empty($param['keyword'])) {
            $query['bool']['must'][] = [
                'multi_match' => [
                    'query' => $param['keyword'],
                    'fields' => [
                        'title',
                        'content',
                        'topic_title_str'
                    ]
                ]
            ];
        }

        $query['bool']['should'][] = [
            'bool' => [
                'must' => [[
                    'term' => [
                        'visible' => ['value' => 1]
                    ]
                ]]
            ]
        ];

        if (!empty($friend_ids)) {
            $query['bool']['should'][] = [
                'bool' => [
                    'must' => [
                        [
                            'terms' => [
                                'uid' => $friend_ids
                            ]
                        ],
                        [
                            'term' => [
                                'visible' => ['value' => 2]
                            ]
                        ]
                    ]
                ]
            ];
        }

        $query['bool']['should'][] = [
            'bool' => [
                'must' => [
                    [
                        'term' => [
                            'uid' => ['value' => $param['uid']]
                        ]
                    ],
                    [
                        'term' => [
                            'visible' => ['value' => 2]
                        ]
                    ]
                ]
            ]
        ];

        return $query;
    }

    public static function exists($id)
    {
        return Article::where('id', $id)->exists();
    }

    public static function searchable($id)
    {
        $article = Article::find($id);
        $article->searchable();
    }

    public static function increment($id, $field)
    {
        Article::where('id', $id)->increment($field);
    }
}