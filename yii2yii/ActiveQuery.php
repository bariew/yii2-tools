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
    public $condition, $order, $together, $group;
    public $scopes = [];

    /**
     *
     */
    public function replaceAttributes()
    {
        if ($this->condition) {
            $this->andWhere($this->condition);
        }
        if ($this->order) {
            $this->orderBy($this->order);
        }
        if ($this->group) {
            $this->groupBy($this->group);
        }
        //if ($this->on)
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

    public static function merge(\yii\db\ActiveQuery $to, $from)
    {
        if (!$from) {
            return $to;
        }
        if (is_array($from)) {
            $from = new self($to->modelClass, $from);
        }
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

        return $to;
    }

    public function mergeWith($query)
    {
        return static::merge($this, $query);
    }

    public function toActiveQuery()
    {
        $this->replaceAttributes();
        $class = $this->modelClass;
        $model = new $class();
        $query = new ActiveQuery($class);
        if (!$this->from) {
            $this->from(['t' => $class::tableName()]);
        }
        if (method_exists($model, 'scopes')) {
            foreach ($model->scopes() as $config) {
                $scope = new static($class, $config);
                $this->mergeWith($scope);
            }
        }
        return static::merge($query, $this);
    }

}