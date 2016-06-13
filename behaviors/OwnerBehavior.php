<?php
/**
 * OwnerBehavior class file.
 * @copyright (c) 2016, Pavel Bariev
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace bariew\yii2Tools\behaviors;


use yii\base\Behavior;
use yii\db\ActiveRecord;


/**
 * Sets user_id and checks whether its owner belongs to the current user.
 * @author Pavel Bariev <bariew@yandex.ru>
 *
 */
class OwnerBehavior extends Behavior
{
    /**
     * @var string
     */
    public $attribute = 'user_id';

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave'
        ];
    }

    /**
     * Sets user_id
     */
    public function beforeSave()
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        $owner->setAttribute($this->attribute, ($this->owner->{$this->attribute} ? : \Yii::$app->user->id));
    }

    /**
     * @param null $user_id
     * @return int|string
     */
    public function isAccessible($user_id = null)
    {
        $user_id = $user_id ? : \Yii::$app->user->id;
        return $this->owner->{$this->attribute} == $user_id;
    }
}