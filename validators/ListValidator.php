<?php
namespace bariew\yii2Tools\validators;

use yii\base\Exception;
use yii\helpers\Inflector;
use yii\validators\Validator;

class ListValidator extends Validator
{
    /**
     * Set it if do not want to involve default {$attribute}List() method
     * @var array
     */
    public $list;

    /**
     * Model for getting list
     * @var \yii\db\ActiveRecord
     */
    public $model;

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        $this->model = $this->model ? : $model;
        $method = Inflector::camelize(str_replace('_id', '', $attribute).'List');
        $list = ($this->list === null) ? array_keys($this->model->$method()) : $this->list;
        $result = is_array($model->$attribute)
            ? !array_diff($model->$attribute, $list)
            : in_array($model->$attribute, $list);
        if (!$result) {
            $this->addError($model, $attribute, ($this->message ? : \Yii::t('app', 'Forbidden value')));
        }
    }
}