<?php
/**
 * Created by PhpStorm.
 * User: pt
 * Date: 25.06.15
 * Time: 19:11
 */

namespace bariew\yii2Tools\helpers;
use yii\db\ActiveRecord;
use yii\jui\DatePicker;

class GridHelper
{
    public static function columnSum($dataProvider, $attributes)
    {
        $result = 0;
        foreach ($dataProvider->models as $model) {
            foreach ((array) $attributes as $attribute) {
                $result += $model[$attribute];
            }
        }
        return $result;
    }

    /**
     * @param $attribute
     * @param ActiveRecord|bool $model
     * @return array
     */
    public static function listFormat($model, $attribute)
    {
        $method = $attribute.'List';
        return [
            'attribute' => $attribute,
            'format' => 'raw',
            'value' => !$model->isNewRecord
                ? $model->$method()[$model->$attribute]
                : function ($data) use ($method, $attribute) {
                    return $data->$method()[$data->$attribute];
                },
            'filter' => $model->$method()
        ];
    }

    public static function dateFormat($model, $attribute, $options = ['class' => 'form-control'])
    {
        $pickerOptions = array_merge([
            'dateFormat' => 'php:Y-m-d',
        ], compact('model', 'attribute', 'options'));
        return [
            'attribute' => $attribute,
            'format' => 'datetime',
            'filter' => DatePicker::widget($pickerOptions)
        ];
    }
}