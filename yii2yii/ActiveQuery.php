<?php
/**
 * Created by PhpStorm.
 * User: pt
 * Date: 22.12.15
 * Time: 12:12
 */

namespace bariew\yii2Tools\yii2yii;


class ActiveQuery extends \yii\db\ActiveQuery
{
    public $condition, $order;
    public $scopes = [];

    private $replacements = [
        'order' => 'orderBy',
        'condition' => 'where',
    ];

    private function replaceAttributes()
    {
        $class = $this->modelClass;
        $table = $class::tableName();
        foreach ($this->replacements as $old => $new) {
            $this->$new = $this->$old;
        }

        if ($this->orderBy) {
            $resultOrders = [];
            $orders = is_array($this->orderBy) ? $this->orderBy : explode(',', $this->orderBy);
            foreach ($orders as $order) {
                $data = explode(' ', trim($order));
                $data[1] = (@$data[1] == 'DESC' ? SORT_DESC : SORT_ASC);
                $resultOrders[$data[0]] = $data[1];
            }
            $this->orderBy = $resultOrders;
        }
        if (is_array($this->where)) {
            array_walk_recursive($this->where, function(&$v) use($table) {
                $v = str_replace('t.', $table.'.', $v);;
            });
        } else {
            $this->where = str_replace('t.', $table.'.', $this->where);
        }
    }

    public function toActiveQuery()
    {
        $this->replaceAttributes();
        $class = $this->modelClass;
        $model = new $class();
        $query = new ActiveQuery($class);
        if (method_exists($model, 'scopes')) {
            foreach ($model->scopes() as $config) {
                $scope = new static($class, $config);
                $scope->replaceAttributes();
                $this->mergeWith($scope);
            }
        }
        return static::merge($query, $this);
    }

    public static function merge(\yii\db\ActiveQuery $to, \yii\db\ActiveQuery $from)
    {
        $to->andWhere($from->where);
        $to->addParams($from->params);
        foreach (['orderBy', 'limit', 'with'] as $param) {
            if (!is_null($from->$param)) {
                $to->$param = $from->$param;
            }
        }
        return $to;
    }

    public function mergeWith($query)
    {
        return static::merge($this, $query);
    }
}