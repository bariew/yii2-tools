<?php
/**
 * AccessBehavior class file.
 * @copyright (c) 2015, Pavel Bariev
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace bariew\yii2Tools\behaviors;

use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;

/**
 * This behavior manages access rights while current user is going
 * to access/find current owner model
 * It sets owners scenario after it model is initiated
 * and provides 'search' method which allows to find only
 * models with 'owner_id' allowed to current user.
 * It needs user to have 'owner_id' too which is the same as the model's owner_id
 * - which means they belong to the same group/Company
 *
 * @property ActiveRecord $owner
 *
 * @author Pavel Bariev <bariew@yandex.ru>
 */
class AccessBehavior extends Behavior
{
    /**
     * @var string
     */
    public $attribute = 'owner_id';

    /**
     * @var string model scenario e.g. Yii::$app->user->identity->getRole()
     */
    public $scenario;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_INIT => 'afterInit',
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave'
        ];
    }

    /**
     * Sets owner scenario according to current user role.
     */
    public function afterInit()
    {
        if ($this->scenario) {
            $this->owner->scenario = $this->scenario;
        }
    }

    /**
     * Automatically sets owners 'owner_id' from current user owner_id
     */
    public function beforeSave()
    {
        if ($this->owner->hasAttribute($this->attribute) && !$this->owner->{$this->attribute}) {
            $this->owner->setAttribute($this->attribute, Yii::$app->user->identity->{$this->attribute});
        }
    }

    /**
     * Searches models matching current user owner_id
     * @param $params
     * @return \yii\db\ActiveQuery
     */
    public function search($params)
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        $query = $owner::find();
        $t = is_array($query->from) ? key($query->from) : $query->from;
        return $query
            ->filterWhere($params)
            ->andWhere(["$t.owner_id" => Yii::$app->user->identity->{$this->attribute}]);
    }

    /**
     * Gets relation data list for dropdown
     * @param $relationName
     * @param string $index
     * @param null $name
     * @return array
     * @throws \Exception
     */
    public function relationList($relationName, $index = 'id', $name = null)
    {
        $model = $this->owner;
        /** @var ActiveRecord $class */
        $class = $model->getRelation($relationName)->modelClass;
        $item = new $class();
        /** @var \yii\db\ActiveQuery $relation */
        $relation = method_exists($item, 'search')
            ? $item->search([])
            : $class::find();
        $name = $name ? : static::getNameAttribute($model, $relationName);
        $result = $relation->select($name)
            ->orderBy($name)
            ->indexBy($index)
            ->column();
        return $result;
    }

    /**
     * @param ActiveRecord $model
     * @param string $relationName
     * @return string
     * @throws \Exception
     */
    protected static function getNameAttribute($model, $relationName)
    {
        /** @var ActiveRecord $class */
        $class = $model->getRelation($relationName)->modelClass;
        $attributes = Yii::$app->db->getTableSchema($class::tableName())->columnNames;
        if (!$existingNames = array_intersect(['name', 'title', 'username'], $attributes)) {
            throw new \Exception("Relation does not have name attribute: ". $class);
        }
        return reset($existingNames);
    }
}