<?php

namespace App\services;

use App\Models\Article;
use App\Models\ArticleTopic;
use App\Models\Friend;
use App\Models\Topic;
use Illuminate\Support\Facades\DB;

class ArticleService
{
    public function add($param)
    {
        $article_data = [
            'title' => $param['title'],
            'content' => $param['content'],
            'pic' => $param['pic'],
            'latitude' => $param['latitude'],
            'longitude' => $param['longitude'],
            'visible' => $param['visible'],
            'iso' => $param['iso'],
            'uid' => $param['uid'],
        ];

        $topic_ids = [];
        $new_topic = [];
        if (!empty($param['topic'])) {
            $topic_json = json_decode($param['topic'], true);
            foreach ($topic_json as $info) {
                if ($info['id'] == 0) {
                    if (!Topic::where('title', $info['title'])->exists()) {
                        $new_topic[] = [
                            'title' => $info['title'],
                            'uid' => $param['uid']
                        ];
                    }
                } else {
                    array_push($topic_ids, $info['id']);
                }
            }

            if (!empty($topic_ids)) {
                $topic_ids = Topic::query()->select('id')->whereIn('id', $topic_ids)->get()->pluck('id')->toArray();
            }
        }

        DB::transaction(function () use ($article_data, $topic_ids, $new_topic) {
            $new_article = Article::create($article_data);
            $article_id = $new_article->id;
            foreach ($new_topic as $topic_data) {
                $res = Topic::create($topic_data);
                array_push($topic_ids, $res->id);
            }

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

    public function search($param)
    {
        $es = Article::es();

        $query = [];
        $query['bool']['must'] = [];

        $friend_ids = Friend::query()->select('friend_id')->where('uid', $param['uid'])->get()->pluck('friend_id')->toArray();

        if (!empty($param['keyword'])) {
            $query['bool']['must'][] = [
                'multi_match' => [
                    'query' => $param['keyword'],
                    'fields' => [
                        'title',
                        'content'
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

        //echo json_encode($query);

        $start = $param['limit'] * ($param['page'] - 1);
        $rs = $es->searchDoc($query, $start, $param['limit'], ['id' => 'desc']);
        $array['total'] = $rs['total'];
        if (isset($rs['data'])) {
            foreach ($rs['data'] as $row) {
                $array['rs'][] = $row['content'];
            }
        }

        return $array;
    }


}