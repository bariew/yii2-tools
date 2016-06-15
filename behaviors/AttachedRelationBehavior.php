<?php
/**
 * AttachedRelationBehavior class file.
 * @copyright (c) 2016, Pavel Bariev
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace bariew\yii2Tools\behaviors;

use yii\base\Behavior;
use yii\db\ActiveRecord;
use Yii;
use bariew\yii2Tools\helpers\FormHelper;

/**
 * Attaches children models post data e.g. when child model creation data places
 * inside parents form and depends on parent's afterSave id.
 *
 * Usage: add to model behaviors() method:
 * return [
 *      ...
 *      [
 *          'class' => 'bariew\yii2Tools\behaviors\AttachedRelationBehavior',
 *          'relations' => ['children'], // you need to have relation function getChildren() defined
 *      ]
 * ];
 * @author Pavel Bariev <bariew@yandex.ru>
 *
 */
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

    /**
     * Validates children models according to their class rules
     */
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

    /**
     * Saves children models
     */
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

    /**
     * Get either saving post data for related models or models themself
     * @param $relation
     * @return array
     */
    public function getRelationSavingModels($relation)
    {
        return isset($this->savingModels[$relation])
            ? $this->savingModels[$relation]
            : $this->owner->$relation;
    }
}