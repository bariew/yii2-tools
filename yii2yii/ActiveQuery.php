<?php
/**
 * Created by PhpStorm.
 * User: pt
 * Date: 22.12.15
 * Time: 12:12
 */

namespace bariew\yii2Tools\yii2yii;


use yii\db\Query;

class ActiveQuery extends \yii\db\ActiveQuery
{
    public $condition, $order, $together;
    public $scopes = [];

    private $replacements = [
        'order' => 'orderBy',
        'condition' => 'where',
    ];

    /**
     *
     */
    public function replaceAttributes()
    {
        $class = $this->modelClass;
        $model = new $class();
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

        if ($this->join && !is_array($this->join)) {
            $this->join = [$this->join];
        }
        if ($this->with) {
            $with = [];
            foreach ($this->with as $name => $value) {
                $relName = is_numeric($name) ? $value : $name;
                if (preg_match('/Count/', $relName)) {
                    continue;
                }
                if (preg_match('/\./', $relName)) {
                    continue;
                }                $with[$name] = $value;
                if (!is_array($value)) {
                    continue;
                }
                /** @var ActiveQuery $relation */
                $relation = call_user_func([$model, "get{$name}"]);
                if ($relation->via) {
                    $relation = $relation->via[1];
                }
                /** @var ActiveQuery $relationQuery */
                $with[$name] = function ($relationQuery) use ($relation, $value) {
                    $newQuery = new ActiveQuery($relation->modelClass, $value);
                    $newQuery->replaceAttributes();
                    $newQuery::merge($relationQuery->via[1], $newQuery);
                };
            }
            $this->with = $with;
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

    public static function merge(\yii\db\ActiveQuery $to, $from)
    {
        if (!$from) {
            return $to;
        }
        if (is_array($from)) {
            $from = new Query($from);
        }
        $class = $to->modelClass;
        $table = $class::tableName();
        /** @var \yii\db\ActiveQuery $from */
        $to->andWhere($from->where);
        $to->addParams($from->params);
        foreach (['orderBy', 'limit', 'with'] as $param) {
            if (!is_null($from->$param)) {
                $to->$param = $from->$param;
            }
        }
        foreach (['join', 'where'] as $param) {
            if ($to->where) {
                static::replaceInArray($to->$param, function(&$v) use($table) {
                    $v = preg_replace('/[\w_]+\./', $table.'.', $v);
                });
            }
        }

        return $to;
    }

    public function mergeWith($query)
    {
        return static::merge($this, $query);
    }

    public static function replaceInArray(&$data, $function)
    {
        if (is_array($data)) {
            array_walk_recursive($data, $function);
            static::arrayWalkRecursiveKeys($data, $function);
        } else {
            call_user_func_array($function, [&$data]);
        }
    }

    public static function arrayWalkRecursiveKeys(&$array, $function)
    {
        if (!is_array($array)) {
            return;
        }
        $result = [];
        foreach ($array as $k => $v) {
            call_user_func_array($function, [&$k]);
            static::arrayWalkRecursiveKeys($v, $function);
            $result[$k] = $v;
        }
        $array = $result;
    }
}