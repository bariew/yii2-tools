<?php
/**
 * GridHelper class file.
 * @copyright (c) 2015, Pavel Bariev
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace bariew\yii2Tools\helpers;
use yii\db\ActiveRecord;
use yii\helpers\Inflector;
use yii\jui\DatePicker;
use Yii;
use yii\base\Model;

/**
 * Helper for GridView, GridList fields display.
 * @author Pavel Bariev <bariew@yandex.ru>
 *
 */
class GridHelper
{
    /**
     * @var array model attribute lists values caching
     */
    private static $lists = [];

    /**
     * Gets sum for GridList model attribute
     * @param $dataProvider
     * @param $attributes
     * @return int
     */
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
     * Gets model method name returning available values list for the attribute.
     * @param $attribute
     * @return string
     */
    public static function listName($attribute)
    {
        return Inflector::camelize(str_replace('_id', '', $attribute).'List');
    }

    /**
     * Renders Grid column for list value
     * @param ActiveRecord|bool $model
     * @param $attribute
     * @param array $options
     * @return array
     */
    public static function listFormat($model, $attribute, $options = [])
    {
        $method = static::listName($attribute);
        $key = get_class($model).$attribute;
        $list = static::$lists[$key] = isset(static::$lists[$key])
            ? static::$lists[$key]
            : $model->$method();
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
     * Renders grid column for list value of via table data
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
            'filter' => false,
            //'visible' => $model->isAttributeSafe($attribute),
        ], $options);
    }

    /**
     * Renders Date format Grid column
     * @param $model
     * @param $attribute
     * @param array $options
     * @param array $pickerOptions
     * @return array
     * @throws \Exception
     */
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

    /**
     * Creates array of replacements for {{modelName_attribute}} placeholders.
     * @param Model[] $models
     * @param array $attributes
     * @param bool $preview
     * @return array
     *
     * @example this will add '{{myModel_title}} | title' string to the detail view
        \yii\widgets\DetailView::widget([
            'model' => false,
            'attributes' => GridHelper::variableReplacements([], ['\MyModel' => ['title']], true),
        ])
     * @example This will make an array ["{{myModel_title}}" => "Hello"]
     * where Hello is the title of \MyModel instance from $models
     * $replacements = GridHelper::variableReplacements($models, ['\MyModel' => ['title']], true)
     */
    public static function variableReplacements(array $models, array $attributes, $preview = false)
    {
        $result = [];
        if (!$models) {
            array_walk($attributes, function ($v, $class) use (&$models) {
                $models[] = new $class();
            });
        }
        foreach ($models as $model) {
            if (!is_object($model)) {
                continue;
            }
            $class = get_class($model);
            foreach ($attributes[$class] as $attribute) {
                $formName = str_replace(['app\modules', 'models', '\\'], ['','_',''], $class);
                $key = strtolower('{{'.$formName.'_'.$attribute.'}}');
                if ($preview) {
                    $result[] = ['label' => $key, 'value' => $model->getAttributeLabel($attribute)];
                } else {
                    $result[$key] = $model->$attribute;
                }
            }
        }
        return $result;
    }
}