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
     * Automatically names and creates foreign key.
     * @param $table
     * @param $columns
     * @param $refTable
     * @param $refColumns
     * @return int
     * @throws \yii\db\Exception
     */
    public static function dropForeignKey($table, $columns, $refTable, $refColumns)
    {
        $name = self::createForeignKeyName($table, $columns, $refTable);
        $fks = Yii::$app->db->schema->getTableSchema($table)->foreignKeys;
        $search = array_merge([$refTable], array_combine((array)$columns, (array)$refColumns));
        if (!in_array($search, $fks)) {
            return true;
        }
        return Yii::$app->db->createCommand()->dropForeignKey($name, $table)->execute();
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

    public static function createIndex($table, $columns, $unique = false)
    {
        return Yii::$app->db->createCommand()
            ->createIndex($table .'_'.implode('-', (array) $columns) . '_idx', $table, $columns, $unique)
            ->execute();
    }

    public static function addPrimaryKey($table, $columns)
    {
        return Yii::$app->db->createCommand()
            ->addPrimaryKey($table .'_pk', $table, $columns)
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

    /**
     * Inserts new data into table or updates on duplicate key.
     * @param string $tableName db table name
     * @param array $columns db column names
     * @param array $data data to insert
     * @param string $db application connection name
     * @return boolean whether operation is successful
     */
    public static function insertUpdate($tableName, $columns, $data, $db = 'db')
    {
        if (!$data) {
            return false;
        }
        foreach ($data as $key => $row) {
            $data[$key] = array_values($row);
        }
        $sql = \Yii::$app->$db->createCommand()->batchInsert($tableName, $columns, $data)->getSql();
        $sql .= 'ON DUPLICATE KEY UPDATE ';
        $values = [];
        foreach ($columns as $column) {
            $values[] = "{$column} = VALUES({$column})";
        }
        $sql .= implode($values, ', ');
        return \Yii::$app->$db->createCommand($sql)->execute();
    }
}