<?php
/**
 * Created by PhpStorm.
 * User: pt
 * Date: 05.05.16
 * Time: 17:40
 */

namespace bariew\yii2Tools\helpers;

use yii\base\Event;
use yii\base\Model;
use yii\db\ActiveRecord;
use yii\helpers\Html;
use yii\helpers\Inflector;

class ModelHelper
{
    /**
     * @param $data
     * @return Model
     */
    public static function initModel($data)
    {
        list($class, $attributes, $dirtyAttributes) = array_values($data);
        /** @var ActiveRecord $model */
        $model = new $class();
        if ($model instanceof ActiveRecord) {
            $model::populateRecord($model, $attributes);
            $model->oldAttributes = $model->primaryKey ? array_diff_key($attributes, $dirtyAttributes) : null;
        } else {
            $model->attributes = $attributes;
        }
        return $model;
    }

    /**
     * @param $class
     * @param $id
     * @param array $options
     * @return string
     */
    public static function getLink($class, $id, $options = [])
    {
        list($app, $module, $moduleName, $model, $modelName) = explode('\\', $class);
        list($modulePath, $modelPath)
            = [Inflector::camel2id($moduleName), Inflector::camel2id($modelName)];
        $modelName = ($modulePath == $modelPath || $modelPath == 'item')
            ? $moduleName
            :  "{$moduleName} {$modelName}";
        return $id
            ? Html::a("{$modelName}#{$id}", ["/{$modulePath}/{$modelPath}/view", 'id' => $id], $options)
            : Html::a("{$modelName}", ["/{$modulePath}/{$modelPath}/index"], $options);
    }

    public static function attributeDifference(Event $event)
    {
        /** @var ActiveRecord $model */
        $model = $event->sender;
        $diff = new \cogpowered\FineDiff\Diff();
        if (!isset($model->dirtyAttributes)) {
            $old = [];
            $new = $model->attributes;
        } else {
            $old = array_intersect_key($model->oldAttributes, $model->dirtyAttributes);
            $new = array_intersect_key($model->attributes, $model->dirtyAttributes);
        }
        unset($old['password'], $new['password']);
        return $diff->render(
            preg_replace(['#[\s\n]*\)#', '#Array[\s\n]*\(#'], ['', ''], print_r($old, true)),
            preg_replace(['#[\s\n]*\)#', '#Array[\s\n]*\(#'], ['', ''], print_r($new, true))
        );
    }
}