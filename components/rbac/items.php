<?php return [
    'default'           => ['type' => 1, 'children' => ['.*'],],
    '.*'                => ['type' => 2],

    'base/site/.*'      => ['type' => 2],
    'user/default/.*'   => ['type' => 2],
    'page/default/.*'   => ['type' => 2],
];
