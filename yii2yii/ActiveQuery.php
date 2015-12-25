<?php
/**
 * Created by PhpStorm.
 * User: pt
 * Date: 22.12.15
 * Time: 12:12
 */

namespace bariew\yii2Tools\yii2yii;


use yii\db\ActiveRecord;
use yii\db\Query;

class ActiveQuery extends \yii\db\ActiveQuery
{
    public $condition, $order, $together;
    public $scopes = [];

    public function __construct($class, $config = [])
    {
        parent::__construct($class, $config);
        $this->replaceAttributes();
    }

    public function setCondition($condition)
    {
        $this->andWhere($condition);
    }

    public function setOrder($order)
    {
        $this->orderBy($order);
    }

    /**
     *
     */
    public function replaceAttributes()
    {
//        foreach ($this->replacements as $old => $new) {
//            $this->$new = $this->$old;
//        }
//        if ($this->orderBy) {
//            $resultOrders = [];
//            $orders = is_array($this->orderBy) ? $this->orderBy : explode(',', $this->orderBy);
//            foreach ($orders as $key => $order) {
//                if (is_numeric($order)) {
//                    $resultOrders[$key] = $order;
//                    continue;
//                }
//                $data = explode(' ', trim($order));
//                $data[1] = (@$data[1] == 'DESC' ? SORT_DESC : SORT_ASC);
//                $resultOrders[$data[0]] = $data[1];
//            }
//            $this->orderBy = $resultOrders;
//        }
        if ($this->condition && !$this->where) {
            $this->andWhere($this->condition);
        }
        if ($this->order && !$this->orderBy) {
            $this->orderBy($this->order);
        }
        /** @var \yii\db\ActiveRecord $model */
        if ($this->select) {
            $this->select = is_array($this->select) ? $this->select : explode(',', $this->select);
            $select = [];
            foreach ($this->select as $key => $string) {
                $pattern = '/(as|AS) (\w+)/';
                if (preg_match($pattern, $string, $matches)) {
                    $key = $matches[2];
                    $string = preg_replace($pattern, '', $string);
                }
                $select[$key] = $string;
            }
            $this->select = $select;
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
                }
                $with[] = $relName;
                //static::replaceTables($value, $model);
                $this->joinWith(
                    [$relName => function($query) use ($value) {
                        return is_array($value)
                            ? ActiveQuery::merge($query, new ActiveQuery($query->modelClass, $value))
                            : true;
                    }],
                    true,
                    (@$value['together'] ? 'RIGHT JOIN' : 'LEFT JOIN')
                );
            }
            $this->with = $with;
        }
    }

    public function toActiveQuery()
    {
        $class = $this->modelClass;
        $model = new $class();
        $query = new ActiveQuery($class);
        if (method_exists($model, 'scopes')) {
            foreach ($model->scopes() as $config) {
                $scope = new static($class, $config);
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
            $from = new self($to->modelClass, $from);
        }
        $class = $from->modelClass = $to->modelClass ?: $from->modelClass;
        if ($from instanceof self) {
            $from->replaceAttributes();
        }
        /** @var \yii\db\ActiveQuery $from */
        if ($from->where) {
            $to->andWhere($from->where);
        }
        $to->addParams($from->params);
        foreach (['orderBy', 'with', 'join', 'on', 'link', 'via', 'joinWith', 'select'] as $param) {
            if (is_null($from->$param)) {
                continue;
            }
            if (is_array($from->$param) && is_array($to->$param)) {
                $to->$param = array_merge($to->$param, $from->$param);
            } else {
                $to->$param = $from->$param;
            }
        }
        $to->from = $to->from ? : $from->from;
        if (!$to->from) {
            $to->from(['t' => $class::tableName()]);
        }
//        foreach ([ 'where', 'orderBy', 'join', 'select'] as $param) {
//            if ($to->$param) {
//                static::replaceTables($to->$param, $model);
//            }
//        }

        return $to;
    }

    public function mergeWith($query)
    {
        return static::merge($this, $query);
    }

    public static function replaceTables(&$data, ActiveRecord $model)
    {
        static::replaceInArray($data, function(&$v) use($model) {
            if (!is_string($v)) {
                return;
            }
            $v = preg_replace_callback('/(\W?)([\w_]+)\./', function($matches) use($model){
                try {$relation = @$model->getRelation($matches[2]);}catch (\Exception  $e){ $relation=false;}
                if ($matches[2] == 't') {
                    $table = $model::tableName();
                } elseif ($relation) {
                    //$class = $relation->modelClass;
                    //$table = $class::tableName();
                    $table = $matches[2];
                } else {
                    $table = $matches[2];
                }
                return $matches[1] . $table.'.';
            }, $v);
        });
    }

    public static function replaceInArray(&$data, $function)
    {
        if (is_object($data)) {
            return;
        } else if (is_array($data)) {
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