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
     * @param ActiveRecord|bool $model
     * @param $attribute
     * @param array $options
     * @return array
     */
    public static function listFormat($model, $attribute, $options = [])
    {
        $method = str_replace('_id', '', $attribute).'List';
        return array_merge([
            'attribute' => $attribute,
            'format' => 'raw',
            'value' => !$model->isNewRecord
                ? $model->$method()[$model->$attribute]
                : function ($data) use ($method, $attribute) {
                    return $data->$method()[$data->$attribute];
                },
            'filter' => $model->$method(),
            'visible' => $model->isAttributeSafe($attribute),
        ], $options);
    }

    public static function dateFormat($model, $attribute, $options = [], $pickerOptions = [])
    {
        $pickerOptions = array_merge([
            'model' => $model,
            'attribute' => $attribute,
            'options' => ['class' => 'form-control'],
        ], $pickerOptions);
        return array_merge([
            'attribute' => $attribute,
            'format' => 'date',
            'filter' => DatePicker::widget($pickerOptions)
        ], $options);
    }
}