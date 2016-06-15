<?php
/**
 * RelationOperator class file.
 * @copyright (c) 2015, Pavel Bariev
 * @license http://www.opensource.org/licenses/bsd-license.php
 */
namespace bariew\yii2Tools\base;


use bariew\yii2Tools\helpers\MigrationHelper;
use yii\db\ActiveRecord;
use yii\db\Query;
use yii\db\ActiveQuery;

/**
 * Helps to manipulate model relations via table data.
 *
 *
 * @author Pavel Bariev <bariew@yandex.ru>
 *
 */
class RelationOperator
{
    /**
     * @var \yii\db\ActiveQuery
     */
    public $relation;

    /**
     * @var ActiveRecord
     */
    public $owner;

    /**
     * @var string
     */
    public $relationName, $viaTable, $relationAttribute;

    /**
     * @var array
     */
    public $condition = [];

    /**
     * @param ActiveRecord $model
     * @param $relationName
     */
    public function __construct(ActiveRecord $model, $relationName)
    {
        $this->owner = $model;
        $this->relationName = $relationName;
        $this->relation = $this->owner->getRelation($relationName);
        /** @var ActiveQuery $via */
        $via = is_array($this->relation->via) ? $this->relation->via[1] : $this->relation->via;
        /** @var \yii\db\ActiveRecord $viaClass */
        $this->viaTable = ($viaClass = $via->modelClass) ? $viaClass::tableName() : reset($via->from);
        $this->relationAttribute = $this->relation->link;
        foreach ($via->link as $viaAttribute => $ownerAttribute) {
            $this->condition[$viaAttribute] = $this->owner->$ownerAttribute;
        }
    }

    /**
     * Gets relation table via unique data
     * @param null $select
     * @return Query
     */
    public function getViaIds($select = null)
    {
        return (new Query())
            ->from($this->viaTable)
            ->select(($select ? : array_values($this->relationAttribute)))
            ->where($this->condition)
            ;
    }

    /**
     * Deletes relation data
     * @param $ids
     * @return bool|int
     * @throws \yii\db\Exception
     */
    public function deleteViaIds($ids)
    {
        return !$ids ||
        \Yii::$app->db->createCommand()->delete(
            $this->viaTable,
            array_merge($this->condition, [reset($this->relationAttribute) => $ids])
        )->execute();
    }

    /**
     * Adds relation data
     * @param $ids
     * @param array $defaultData
     * @return bool
     */
    public function addViaIds($ids, $defaultData = [])
    {
        if (!$ids) {
            return true;
        }
        foreach ($ids as $key => $id) {
            $id = is_array($id)
                ? $id
                : [reset($this->relationAttribute) => $id];
            $ids[$key] = array_merge($id, $this->condition, $defaultData);
        }
        MigrationHelper::insertUpdate($this->viaTable, array_keys(reset($ids)), $ids);
    }

    /**
     * Clones original model relations to target model
     * @param ActiveRecord $target
     * @param array $except exclude fields from cloning
     * @return mixed
     */
    public function cloneRelation(ActiveRecord $target, $except = ['id'])
    {
        $ids = $this->getViaIds(['*'])->all();
        array_walk($ids, function (&$v) use ($except) {
            $v = array_diff_key($v, array_flip($except));
        });
        return (new static($target, $this->relationName))->addViaIds($ids);
    }

    /**
     * Clones relations from one models array to another
     * @param $sources
     * @param $clones
     * @param array $relations relation names
     */
    public static function cloneRelations($sources, $clones, $relations = [])
    {
        if (!is_array($sources)) {
            $sources = [$sources];
            $clones = [$clones];
        }
        foreach ($sources as $k => $source) {
            foreach ($relations as $j => $name) {
                list($name, $except) = is_array($name) ? [$j, $name] : [$name, ['id']];
                (new static($source, $name))->cloneRelation($clones[$k], $except);
            }
        }
    }
}