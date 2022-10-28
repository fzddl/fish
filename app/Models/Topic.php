<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Topic extends Model
{
    use HasFactory, Searchable;

    protected $fillable = ['title', 'uid', 'sort', 'pic', 'article_count', 'visited_count', 'follow_count'];

    public function toSearchableArray()
    {
        $array = $this->toArray();

        return $array;
    }
}
