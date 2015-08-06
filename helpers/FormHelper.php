<?php
/**
 * Created by PhpStorm.
 * User: pt
 * Date: 05.08.15
 * Time: 17:25
 */

namespace bariew\yii2Tools\helpers;
use yii\base\Model;
use yii\db\ActiveRecord;


class FormHelper
{
    /**
     * Loads posted data into model relation models
     * @param ActiveRecord $model
     * @param $relationName
     * @param $data
     * @return array
     */
    public static function loadRelation(ActiveRecord $model, $relationName, $data)
    {
        $relationClass = $model->getRelation($relationName)->modelClass;
        return static::loadMultiple(
            @$data[(new $relationClass())->formName()],
            $relationClass,
            $model->$relationName
        );
    }

    public static function loadMultiple($data, $modelClass, $models = [])
    {
        $result = ['models' => $models, 'errors' => []];
        if (!$data) {
            return $result;
        }
        $lastIndex = count($models);
        foreach ($data as $id => $attributes) {
            $modelSearch = array_filter($models, function($v) use($id){return $v['id'] == $id;});
            /** @var Model $model */
            $model = reset($modelSearch) ? : new $modelClass();
            $model->load($attributes, '');
            $index = $modelSearch ? key($modelSearch) : $lastIndex++;
            $result['models'][$index] = $model;
            if (!$model->validate()) {
                $result['errors'][$index] = $model->errors;
            }
        }
        return $result;
    }
}