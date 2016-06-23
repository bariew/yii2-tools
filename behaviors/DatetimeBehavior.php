<?php
/**
 * Created by PhpStorm.
 * User: pt
 * Date: 23.06.16
 * Time: 2:24
 */

namespace bariew\yii2Tools\behaviors;


use yii\base\Behavior;
use yii\db\ActiveRecord;

class DatetimeBehavior extends Behavior
{
    /**
     * @var string|array Format of the attributes in the database
     */
    public $originalFormat = 'php:U';

    /**
     * @var string|array Format of the attribute that should be shown to the user
     */
    public $targetFormat = 'datetime';

    /**
     * @var array List of the model attributes in one of the following formats:
     */
    public $attributes = [];

    public $formatter;

    /**
     * @var array
     */
    private $attributeValues = [];

    private function format($value, $format)
    {
        switch ($format) {
            case 'date';
                return \Yii::$app->formatter->asDate($value);
            case 'time';
                return \Yii::$app->formatter->asTime($value);
            case 'php:U';
            case 'timestamp';
                return strtotime($value);
            default:
                return \Yii::$app->formatter->asDatetime($value);
        }
    }
    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
            ActiveRecord::EVENT_AFTER_FIND => 'afterFind',
        ];
    }

    /**
     *
     */
    public function beforeSave()
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        foreach ($this->attributes as $attribute) {
            if (!$value = @$this->attributeValues[$attribute.'_local']) {
                continue;
            }
            $owner->setAttribute($attribute, $this->format($value, $this->originalFormat));
        }
    }

    public function afterFind()
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        foreach ($this->attributes as $attribute) {
            $value = $owner->getAttribute($attribute);
            $this->attributeValues["{$attribute}_local"] = $value
                ? $this->format($value, $this->targetFormat)
                : '';
        }
    }

    /**
     * @inheritdoc
     */
    public function canGetProperty($name, $checkVars = true)
    {
        if ($this->hasAttribute($name)) {
            return true;
        }

        return parent::canGetProperty($name, $checkVars);
    }

    /**
     * @inheritdoc
     */
    public function hasAttribute($name)
    {
        return in_array(preg_replace('#^(.*)_local$#', '$1', $name), $this->attributes);
    }

    /**
     * @inheritdoc
     */
    public function canSetProperty($name, $checkVars = true)
    {
        if ($this->hasAttribute($name)) {
            return true;
        }

        return parent::canSetProperty($name, $checkVars);
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        if ($this->hasAttribute($name)) {
            return @$this->attributeValues[$name];
        }

        return parent::__get($name);
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        if ($this->hasAttribute($name)) {
            $this->attributeValues[$name] = $value;
            return;
        }
        parent::__set($name, $value);
    }
}