<?php
/**
 * SerializeBehavior class file.
 * @copyright (c) 2015, Pavel Bariev
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace bariew\yii2Tools\behaviors;
use yii\base\Behavior;
use yii\base\Exception;
use yii\db\ActiveRecord;

/**
 * ActiveRecord behavior for serializing/json encoding
 * array data before saving it to db
 *
 * Usage: set in model
    public function behaviors()
    {
        return [
            ['class' => 'bariew\yii2Tools\behaviors\SerializeBehavior', 'attributes' => ['myAttribute']]
       ];
    }
 *
 * @property ActiveRecord $owner
 * @author Pavel Bariev <bariew@yandex.ru>
 */
class SerializeBehavior extends Behavior
{
    const TYPE_JSON = 'json';
    const TYPE_PHP = 'php';

    public $attributes = [];
    public $type = self::TYPE_JSON;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_FIND => 'unserialize',
            ActiveRecord::EVENT_BEFORE_INSERT => 'serialize',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'serialize'
        ];
    }

    /**
     * Serializes data
     * @throws Exception
     */
    public function serialize()
    {
        foreach ($this->attributes as $attribute) {
            switch ($this->type) {
                case static::TYPE_JSON :
                    $value = json_encode($this->owner->getAttribute($attribute));
                    break;
                case static::TYPE_PHP :
                    $value = serialize($this->owner->getAttribute($attribute));
                    break;
                default: throw new Exception("Unknown type: ". $this->type);
            }
            $this->owner->setAttribute($attribute, $value);
        }
    }

    /**
     * Unseriaizes data
     * @throws Exception
     */
    public function unserialize()
    {
        foreach ($this->attributes as $attribute) {
            switch ($this->type) {
                case static::TYPE_JSON :
                    $value = json_decode($this->owner->getAttribute($attribute), true);
                    break;
                case static::TYPE_PHP :
                    $value = unserialize($this->owner->getAttribute($attribute));
                    break;
                default: throw new Exception("Unknown type: ". $this->type);
            }
            $this->owner->setAttribute($attribute, $value);
        }
    }
}
