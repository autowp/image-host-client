<?php

namespace Application;

$imageDir = __DIR__ . '/../public/image/';

$host = getenv('IMAGE_HOST_HOST');

return [
    'imageStorage' => [
        'imageTableName'         => 'image',
        'formatedImageTableName' => 'formated_image',
        'fileMode'               => 0644,
        'dirMode'                => 0755,

        'dirs' => [
            'format' => [
                'path' => $imageDir . "format",
                'url'  => 'http://' . $host . '/image/format/',
                'namingStrategy' => [
                    'strategy' => 'pattern'
                ]
            ],
            'foo' => [
                'path' => $imageDir . "foo",
                'url'  => 'http://' . $host . '/image/foo/',
                'namingStrategy' => [
                    'strategy' => 'pattern'
                ]
            ],
            'bar' => [
                'path' => $imageDir . "bar",
                'url'  => 'http://' . $host . '/image/bar/',
                'namingStrategy' => [
                    'strategy' => 'serial',
                    'options'  => [
                        'deep' => 2
                    ]
                ]
            ]
        ],

        'formatedImageDirName' => 'format',

        'formats' => [
            'baz'    => [
                'fitType'    => 0,
                'width'      => 160,
                'height'     => 120,
                'background' => '#fff',
                'strip'      => 1
            ]
        ]
    ]
];
