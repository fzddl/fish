<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ArticleRequest extends BaseRequest
{
    public function ruleAdd()
    {
        return [
            'title' => 'required|min:2|max:100',
            'content' => 'required',
            'topic' => 'json',
        ];
    }

    public function ruleSearch()
    {
        return [
            'page' => 'required|int|min:1',
            'limit' => 'required|int|min:1|max:50',
            'keyword' => 'min:2',
            'type' => ['required', Rule::in(['follow', 'recommend', 'mine'])]
        ];
    }

    public function ruleFavorite()
    {
        return [
            'article_id' => 'required|int|min:1',
            'status' => ['required', Rule::in(['on', 'off'])]
        ];
    }

    public function ruleVote()
    {
        return [
            'article_id' => 'required|int|min:1',
            'type' => ['required', Rule::in(['up', 'down'])],
            'status' => ['required', Rule::in(['on', 'off'])]
        ];
    }

}
