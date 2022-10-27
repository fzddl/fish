<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class CommentRequest extends BaseRequest
{
    public function ruleAdd()
    {
        return [
            'article_id' => 'required|int|min:1',
            'content' => 'required',
        ];
    }

    public function ruleReply()
    {
        return [
            'article_id' => 'required|int|min:1',
            'at_uid' => 'int|min:0',
            'comment_id' => 'required|int|min:1',
            'content' => 'required',
        ];
    }

    public function ruleList()
    {
        return [
            'page' => 'required|int|min:1',
            'limit' => 'required|int|min:1|max:50',
            'article_id' => 'required|int|min:1',
            'order_type' => [Rule::in(['new', 'recommend'])]
        ];
    }

}
