<?php

namespace App\Models;

use App\Helper\Es7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Article extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'title', 'pic', 'content', 'latitude', 'longitude', 'visible', 'iso', 'ip',
        'uid', 'topic_id_str', 'topic_title_str'
    ];

    public static function es()
    {
        $config = ['index' => 'articles'];
        return new Es7($config);
    }
}
