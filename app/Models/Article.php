<?php

namespace App\Models;

use App\Helper\Es7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Article extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'title', 'pic', 'content', 'latitude', 'longitude', 'local_name', 'visible', 'iso', 'ip',
        'uid', 'topic_id_str', 'topic_title_str', 'vote_up_count', 'vote_down_count'
    ];

    public function toSearchableArray()
    {
        $array = $this->toArray();

        $array['location'] = $array['longitude'] . ',' . $array['latitude'];

        unset($array['latitude'], $array['longitude']);

        return $array;
    }

    public static function createIndex($es)
    {
        $properties = [

            'id' => [
                'type' => 'integer',
            ],
            'uid' => [
                'type' => 'integer',
            ],
            'title' => [
                'type' => 'text',
            ],
            'content' => [
                'type' => 'text'
            ],
            'topic_title_str' => [
                'type' => 'text'
            ],
            'visible' => [
                'type' => 'integer',
            ],
            'vote_up_count' => [
                'type' => 'integer',
            ],
            'vote_down_count' => [
                'type' => 'integer',
            ],
            'local_name' => [
                'type' => 'keyword',
            ],
            'ip' => [
                'type' => 'keyword'
            ],
            'iso' => [
                'type' => 'keyword'
            ],
            'created_time' => [
                'type' => 'date'
            ],
            'topic_id_str' => [
                'type' => 'keyword'
            ],
            'location' => [
                'type' => 'geo_point'
            ],
        ];

        if (!$es->existIndex()) {
            $es->createIndex($properties, 1, 1);
        }
    }

    public static function es()
    {
        $config = ['index' => 'articles'];
        $es = new Es7($config);

        self::createIndex($es);

        return $es;
    }
}
