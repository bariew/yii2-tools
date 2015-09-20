<?php
namespace bariew\yii2Tools\validators;

use yii\helpers\Inflector;
use yii\validators\Validator;

class ListValidator extends Validator
{
    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        $method = Inflector::camelize(str_replace('_id', '', $attribute).'List');
        $result = in_array($model->$attribute, array_keys($model->$method()));
        if (!$result) {
            $this->addError($model, $attribute, ($this->message ? : \Yii::t('app', 'Forbidden value')));
        }
    }
}