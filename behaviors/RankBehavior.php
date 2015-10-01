<?php
/**
 * RankBehavior class file.
 * @copyright (c) 2015, Pavel Bariev
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace bariew\yii2Tools\behaviors;
use yii\base\Behavior;
use yii\db\ActiveRecord;

/**
 * This behavior automatically sets and removes owner's rank
 * @property ActiveRecord $owner
 * @author Pavel Bariev <bariew@yandex.ru>
 */
class RankBehavior extends Behavior
{
    /**
     * @var string owner attribute for rank data
     */
    public $attribute = 'rank';

    /**
     * @var \yii\db\ActiveQuery for calling other owner class models with rank
     */
    public $siblingQueryCallback;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'rankAdd',
            ActiveRecord::EVENT_AFTER_DELETE => 'rankRemove'
        ];
    }

    /**
     * Adds inserts rank into new model
     */
    public function rankAdd()
    {
        if (!is_numeric($this->owner->getAttribute($this->attribute))) {
            $this->owner->setAttribute($this->attribute, $this->getSiblings()->count());
        }
    }

    /**
     * Updates siblings ranks after owner model is deleted
     */
    public function rankRemove()
    {
        $owner = $this->owner;
        $query = $this->getSiblings()->andWhere(['>', $this->attribute, $this->owner->getAttribute($this->attribute)]);
        $owner::updateAllCounters(
            [$this->attribute => -1],
            $query->where
        );
    }

    /**
     * Switches rank position with another model.
     * @param integer $to another model current rank position.
     */
    public function rankSwitch($to)
    {
        $owner = $this->owner;
        $this->getSiblings()->andWhere([$this->attribute => $to])->one()->updateAttributes([
            $this->attribute => $this->owner->getAttribute($this->attribute)
        ]);
        $owner->updateAttributes([$this->attribute => $to]);
    }


    /**
     * Gets owner siblings models
     * @return \yii\db\ActiveQuery
     */
    private function getSiblings()
    {
        return call_user_func([$this->owner, $this->siblingQueryCallback]);
    }
}
