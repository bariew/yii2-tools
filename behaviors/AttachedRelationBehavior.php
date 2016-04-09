<?php
/**
 * Created by PhpStorm.
 * User: pt
 * Date: 08.04.16
 * Time: 11:43
 */

namespace bariew\yii2Tools\behaviors;

use yii\base\Behavior;
use yii\db\ActiveRecord;
use Yii;
use bariew\yii2Tools\helpers\FormHelper;

class AttachedRelationBehavior extends Behavior
{
    public $relations = [];
    private $savingModels = [];

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_VALIDATE => 'beforeValidate',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
        ];
    }

    public function beforeValidate()
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        foreach ($this->relations as $relation) {
            $ruleLoad = FormHelper::loadRelation($owner, $relation, Yii::$app->request->post());
            $this->savingModels[$relation] = $ruleLoad['models'];
            foreach ($ruleLoad['errors'] as $errors) {
                $owner->addError($relation, '');
            }
        }
    }

    public function afterSave()
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        /** @var ActiveRecord[] $models */
        foreach ($this->savingModels as $relation => $models) {
            $link = $owner->getRelation($relation)->link;
            /** @var  $model */
            foreach ($models as $model) {
                foreach ($link as $relationAttribute => $ownerAttribute) {
                    $model->$relationAttribute = $owner->$ownerAttribute;
                }
                $model->save();
            }
        }
    }

    public function getRelationSavingModels($relation)
    {
        return (array) @$this->savingModels[$relation];
    }
}