<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = ['uid', 'article_id', 'content', 'iso', 'ip', 'created_at'];

    const UPDATED_AT = null;
}
