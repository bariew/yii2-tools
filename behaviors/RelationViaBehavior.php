<?php
/**
 * RelationViaBehavior class file.
 * @copyright (c) 2015, Pavel Bariev
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace bariew\yii2Tools\behaviors;
use bariew\yii2Tools\base\LDynamicModel;
use bariew\yii2Tools\base\RelationOperator;
use yii\base\Behavior;
use yii\db\ActiveRecord;

/**
 * Description.
 *
 * Usage:
 * Add this class to owner behaviors() method:
 *
     [
        'class' => RelationViaBehavior::className(),
        'relations' => ['customers', 'campaigns', 'sections']
    ]
 *
 * If you want to validate relations, add rule with specific 'when' function:
 *  'when' => function($model){ return $model instanceof DynamicModel;}
 * This will also require adding 'set{RelationName}(){}' method - body may be empty.
 *
 *
 * @property ActiveRecord $owner
 * @author Pavel Bariev <bariew@yandex.ru>
 */
class RelationViaBehavior extends Behavior
{
    /**
     * @var array strings, relation names you want to process by behavior
     */
    public $relations = [];

    /**
     * Extracts rules for relations only.
     * @return array validation rules.
     */
    protected function getRules()
    {
        $result = [];
        $relationNames = [];
        foreach ($this->relations as $key => $value) {
            $relationNames[] = is_array($value) ? $key : $value;
        }

        foreach ($this->owner->rules() as $key => $rule) {
            $attributes = is_array($rule[0]) ? $rule[0] : [$rule[0]];
            if (!$relations = array_intersect($relationNames, $attributes)) {
                continue;
            }
            $rule[0] = $relations;
            $result[$key] = $rule;
        }
        return $result;
    }

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
     * @throws \yii\base\InvalidConfigException
     */
    public function beforeValidate()
    {
        $model = LDynamicModel::validateModelData($this->relationPost, $this->getRules(), $this->owner);
        foreach ($model->firstErrors as $attribute => $error) {
            $this->owner->addError($attribute, $error);
        }
    }

    /**
     * Adds/removes relations sent with POST from via table
     */
    public function afterSave()
    {
        /** @var ActiveRecord $owner */
        foreach ($this->relations as $key => $value) {
            $relationName = is_array($value) ? $key : $value;
            $defaultData = is_array($value) ? $value : [];
            if (!isset($this->relationPost[$relationName])) {
                continue;
            }
            $newIds = (array) $this->relationPost[$relationName];
            $newIds = array_unique(array_filter($newIds, function($v){return !empty($v);}));
            $relationOperator = new RelationOperator($this->owner, $relationName);
            $oldIds = $relationOperator->getViaIds()->column();
            $relationOperator->deleteViaIds(array_diff($oldIds, $newIds));
            $relationOperator->addViaIds(array_diff($newIds, $oldIds), $defaultData);
        }
    }

    public $relationPost = [];
    public function setRelation($name, $value)
    {
        $this->relationPost[$name] = $value;
    }

    /**
     * @inheritdoc
     */
    public function canSetProperty($name, $checkVars = true)
    {
        return in_array($name, $this->relations) || parent::canSetProperty($name, $checkVars);
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        if (in_array($name, $this->relations)) {
            return $this->setRelation($name, $value);
        }
        return parent::__set($name, $value);
    }
}