<?php
/**
 * RelationViaBehavior class file.
 * @copyright (c) 2015, Pavel Bariev
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace bariew\yii2Tools\behaviors;
use yii\base\Behavior;
use yii\base\DynamicModel;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\validators\Validator;
use yii\web\Application;
use yii\web\Request;

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
     * @var array POST data
     */
    public $post = [];

    /**
     * Extracts rules for relations only.
     * @return array validation rules.
     */
    protected function getRules()
    {
        $result = [];
        foreach ($this->owner->rules() as $key => $rule) {
            $attributes = is_array($rule[0]) ? $rule[0] : [$rule[0]];
            if (!$relations = array_intersect($this->relations, $attributes)) {
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
        if (!\Yii::$app->request instanceof Request) {
            return;
        }
        if (!$this->post = \Yii::$app->request->post($this->owner->formName())) {
            return;
        }
        $model = LDynamicModel::validateModelData($this->post, $this->getRules(), $this->owner);
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
        foreach ($this->relations as $relationName) {
            if (!isset($this->post[$relationName])) {
                continue;
            }
            $newIds = (array) $this->post[$relationName];
            $newIds = array_unique(array_filter($newIds, function($v){return !empty($v);}));
            $relationOperator = new RelationOperator($this->owner, $relationName);
            $oldIds = $relationOperator->getViaIds()->column();
            $relationOperator->deleteViaIds(array_diff($oldIds, $newIds));
            $relationOperator->addViaIds(array_diff($newIds, $oldIds));
        }
    }
}