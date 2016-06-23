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
 *
 * @property \yii\db\ActiveRecord owner
 *
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
            ActiveRecord::EVENT_BEFORE_INSERT => 'attachRelations',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'attachRelations',
            ActiveRecord::EVENT_AFTER_INSERT => 'attachRelations',
            ActiveRecord::EVENT_AFTER_UPDATE => 'attachRelations',
        ];
    }

    /**
     * Validates children models according to their class rules
     */
    public function beforeValidate()
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        foreach ($this->relations as $key => $relation) {
            $data = is_array($relation) ? $relation : [];
            $relation = is_array($relation) ? $key : $relation;
            $ruleLoad = FormHelper::loadRelation($owner, $relation, Yii::$app->request->post(), $data);
            $this->savingModels[$relation] = $ruleLoad['models'];
            foreach ($ruleLoad['errors'] as $errors) {
                $owner->addError($relation, json_encode($errors, JSON_UNESCAPED_UNICODE));
            }
        }
    }

    public function attachRelations()
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        $savingModels = $this->savingModels;
        /** @var ActiveRecord[] $models */
        foreach ($this->savingModels as $relation => $models) {
            $link = $owner->getRelation($relation)->link;
            foreach ($models as $key => $model) {
                foreach ($link as $relationAttribute => $ownerAttribute) {
                    if ((array) $relationAttribute == $model->primaryKey()) {   // model is parent
                        $model->save();
                        $owner->$ownerAttribute = $model->$relationAttribute;
                        unset($savingModels[$relation][$key]);
                    } else if ($owner->$ownerAttribute) {                       // owner is parent
                        $model->$relationAttribute = $owner->$ownerAttribute;
                        $model->save();
                        unset($savingModels[$relation][$key]);
                    }
                }
            }
        }
        $this->savingModels = $savingModels;
    }

    /**
     * Get either saving post data for related models or models themself
     * @param $relation
     * @return array
     */
    public function getRelationSavingModels($relation)
    {
        $result = isset($this->savingModels[$relation])
            ? $this->savingModels[$relation]
            : $this->owner->$relation;
        return is_array($result) && (!$this->owner->getRelation($relation)->multiple)
            ? reset($result) : $result;
    }
}