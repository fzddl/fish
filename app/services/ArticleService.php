<?php

namespace App\services;

use App\Models\Article;
use App\Models\ArticleTopic;
use App\Models\Friend;
use App\Models\Topic;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ArticleService
{
    public function add($param)
    {
        $article_data = $param;

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
                            'uid' => $param['uid']
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

    public static function exists($id)
    {
        return Article::where('id', $id)->exists();
    }

    public static function increment($id, $field)
    {
        Article::where('id', $id)->increment($field);
    }
}