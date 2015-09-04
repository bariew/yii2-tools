<?php
/**
 * Created by PhpStorm.
 * User: pt
 * Date: 25.06.15
 * Time: 19:11
 */

namespace bariew\yii2Tools\helpers;
use yii\db\ActiveRecord;
use yii\helpers\Inflector;
use yii\jui\DatePicker;
use Yii;
class GridHelper
{
    private static $lists = [];

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
        $method = Inflector::camelize(str_replace('_id', '', $attribute).'List');
        $key = get_class($model).$attribute;
        $list = static::$lists[$key] = isset(static::$lists[$key]) ? static::$lists[$key] : $model->$method();
        return array_merge([
            'attribute' => $attribute,
            'format' => 'raw',
            'value' => !$model->isNewRecord
                ? @$list[$model->$attribute]
                : function ($data) use ($list, $attribute) {
                    return @$list[$data->$attribute];
                },
            'filter' => $list,
            'visible' => $model->isAttributeSafe($attribute),
        ], $options);
    }

    /**
     * @param ActiveRecord|bool $model
     * @param $attribute
     * @param array $options
     * @return array
     * @throws \Exception
     */
    public static function viaListFormat($model, $attribute, $options = [])
    {
        $relation = $model->getRelation($attribute);
        $relationClass = $relation->modelClass;
        $columns = Yii::$app->db->getTableSchema($relationClass::tableName())->columnNames;
        $titles = array_intersect(['title', 'name', 'username'], $columns);
        if (!$title = reset($titles)) {
            throw new \Exception(Yii::t('app', 'Relation does not have any title column'));
        }
        return array_merge([
            'attribute' => $attribute,
            'format' => 'raw',
            'value' => !$model->isNewRecord
                ? implode(', ', $relation->select($title)->column())
                : function ($data) use ($attribute, $title) {
                    return implode(', ', $data->getRelation($attribute)->select($title)->column());
                },
            //'filter' => $model->$method(),
            //'visible' => $model->isAttributeSafe($attribute),
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