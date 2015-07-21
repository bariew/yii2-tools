<?php

namespace bariew\yii2Tools\helpers;
use Yii;

class MigrationHelper
{

    /**
     * Unsets foreign key checking it's useful for dropping foreign keys, tables etc.
     * CAUTION! Switch it on after migration with setForeignKeyCheck().
     * @return int
     * @throws \yii\db\Exception
     */
    public static function unsetForeignKeyCheck()
    {
        return Yii::$app->db->createCommand("SET FOREIGN_KEY_CHECKS = 0")->execute();
    }

    /**
     * Sets foreign key checking ON.
     * @return int
     * @throws \yii\db\Exception
     */
    public static function setForeignKeyCheck()
    {
        return Yii::$app->db->createCommand("SET FOREIGN_KEY_CHECKS = 1")->execute();
    }

    /**
     * Automatically names and creates foreign key.
     * @param $table
     * @param $columns
     * @param $refTable
     * @param $refColumns
     * @param null $delete
     * @param null $update
     * @return int
     * @throws \yii\db\Exception
     */
    public static function addForeignKey($table, $columns, $refTable, $refColumns, $delete = null, $update = null)
    {
        $name = self::createForeignKeyName($table, $columns, $refTable);
        return Yii::$app->db->createCommand()->addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete, $update)->execute();
    }

    /**
     * Names new foreign key by convention.
     * @param $table
     * @param $columns
     * @param $refTable
     * @return string
     */
    public static function createForeignKeyName($table, $columns, $refTable)
    {
        return "FK_{$table}_{$refTable}";
    }

    public static function addIndex($table, $columns, $unique = false)
    {
        return Yii::$app->db->createCommand()
            ->createIndex($table .'_'.implode('-', $columns) . '_idx', $table, $columns, $unique)
            ->execute();
    }

    /**
     * @param $data
     * @param array $adds
     * @return mixed
     */
    public static function toBatchData($data, $adds = [])
    {
        foreach ($data as $k => $v) {
            $data[$k] = array_merge([$v], $adds);
        }
        return $data;
    }
}