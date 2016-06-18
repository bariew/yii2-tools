<?php
/**
 * FormHelper class file.
 * @copyright (c) 2015, Pavel Bariev
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace bariew\yii2Tools\helpers;
use yii\base\Model;
use yii\db\ActiveRecord;

/**
 * Helps with form processing.
 *
 * @author Pavel Bariev <bariew@yandex.ru>
 *
 */
class FormHelper
{
    /**
     * Loads posted data into model relation models
     * @param ActiveRecord $model
     * @param $relationName
     * @param $data
     * @param array $requiredData
     * @return array
     */
    public static function loadRelation(ActiveRecord $model, $relationName, $data, $requiredData = [])
    {
        $relationClass = $model->getRelation($relationName)->modelClass;
        return static::loadMultiple(
            @$data[(new $relationClass())->formName()],
            $relationClass,
            $model->$relationName,
            $requiredData
        );
    }

    /**
     * Inserts data into multiple models.
     * @param $data
     * @param $modelClass
     * @param array $models
     * @param array $requiredData
     * @return array
     */
    public static function loadMultiple($data, $modelClass, $models = [], $requiredData = [])
    {
        $result = ['models' => $models, 'errors' => []];
        if (!$data) {
            return $result;
        }
        if (!is_array($models)) {
            $data =  [@$models->id => $data];
            $models = [@$models->id => $models];
            $result = ['models' => $models, 'errors' => []];
        }
        $lastIndex = count($models);
        foreach ($data as $id => $attributes) {
            $modelSearch = array_filter($models, function($v) use($id){return $v['id'] == $id;});
            /** @var Model $model */
            $model = reset($modelSearch) ? : new $modelClass();
            foreach ($requiredData as $k => $v) {
                $model->$k = $v;
            }
            $model->load($attributes, '');
            $index = $modelSearch ? key($modelSearch) : $lastIndex++;
            $result['models'][$index] = $model;
            $model->isAttributeSafe('scenario');
            if (!$model->validate()) {
                $result['errors'][$index] = $model->errors;
            }
        }
        return $result;
    }

    /**
     * Emulates file uploading with existing file.
     * @param $model
     * @param $attribute
     * @param array $options =
     * ['name' => 'file.jpg', 'type' => 'image/jpeg', 'tmp_name' => '/tmp/asdZXC', 'error' => 0, 'size' => 123123]
     */
    public static function setUploadedFile(Model $model, $attribute, $options = [])
    {
        foreach ($options as $name => $value) {
            $_FILES[$model->formName()][$name][$attribute] = $value;
        }
    }
}