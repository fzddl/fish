<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleTopic extends Model
{
    use HasFactory;

    protected $table = 'article_topic';

    public $timestamps = false;

    protected $fillable = ['article_id', 'topic_id'];
}
