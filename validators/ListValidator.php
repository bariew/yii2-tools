<?php
namespace bariew\yii2Tools\validators;

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
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        $method = Inflector::camelize(str_replace('_id', '', $attribute).'List');
        $list = ($this->list === null) ? array_keys($model->$method()) : $this->list;
        $result = in_array($model->$attribute, $list);
        if (!$result) {
            $this->addError($model, $attribute, ($this->message ? : \Yii::t('app', 'Forbidden value')));
        }
    }
}