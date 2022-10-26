<?php
return [
    'indices' => [
        'mappings' => [
            'cqc_blog_article' => [ //索引名称
                "properties" => [
                    "markdown" => [
                        "type" => "text",
                        "analyzer" => "ik_max_word", //插入文档时，将text类型的字段做分词然后插入倒排索引，此时就可能用到analyzer指定的分词器
                        "search_analyzer" => "ik_smart" //在查询时，先对要查询的text类型的输入做分词，再去倒排索引搜索，此时就可能用到search_analyzer指定的分词器
                    ],
                    "tags" => [
                        "type" => "text",
                        "analyzer" => "ik_max_word",
                        "search_analyzer" => "ik_smart"
                    ],
                    "title" => [
                        "type" => "text",
                        "analyzer" => "ik_max_word",
                        "search_analyzer" => "ik_smart"
                    ]
                ]
            ]
        ]
    ],
];
