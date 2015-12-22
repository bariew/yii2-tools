<?php
/**
 * Created by PhpStorm.
 * User: pt
 * Date: 22.12.15
 * Time: 12:08
 */

namespace bariew\yii2Tools\yii2yii;


class ActiveDataProvider extends \yii\data\ActiveDataProvider
{
    public function __construct($config = [])
    {
        $query = is_object($config['query'])
            ? $config['query']
            : new ActiveQuery($config['query']['modelClass'], $config['query']);
        $config['query'] = $query->toActiveQuery();
        return parent::__construct($config);
    }
}