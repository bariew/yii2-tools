<?php
/**
 * MigrationHelper class file.
 * @copyright (c) 2015, Pavel Bariev
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace bariew\yii2Tools\helpers;
use Yii;
use yii\db\TableSchema;

/**
 * See README
 *
 * @author Pavel Bariev <bariew@yandex.ru>
 *
 */
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
        return preg_replace('/[^\w_-]/', '', "FK_{$table}_{$refTable}");
    }

    /**
     * Creates index with common name.
     * @param $table
     * @param $columns
     * @param bool $unique
     * @return int
     * @throws \yii\db\Exception
     */
    public static function createIndex($table, $columns, $unique = false)
    {
        return Yii::$app->db->createCommand()
            ->createIndex($table .'_'.implode('-', (array) $columns) . '_idx', $table, $columns, $unique)
            ->execute();
    }

    /**
     * Adds PK with common name
     * @param $table
     * @param $columns
     * @return int
     * @throws \yii\db\Exception
     */
    public static function addPrimaryKey($table, $columns)
    {
        return Yii::$app->db->createCommand()
            ->addPrimaryKey($table .'_pk', $table, $columns)
            ->execute();
    }

    /**
     * Merges array sub-arrays with another array which data is common for all sub-array elements.
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

    /**
     * Finds all foreign keys in the table and related to the table column from outer tables.
     * @param $tableName
     * @return array ['inner'=>[], 'outer'=>[]]
     */
    public static function tableForeignKeys($tableName)
    {
        $result = ['inner' => [], 'outer' => []];
        foreach (Yii::$app->db->schema->tableSchemas as $table) {
            $foreignKeys = self::findConstraints($table);
            /** @var TableSchema $table */
            if ($table->name == $tableName) {
                $result['inner'] = $foreignKeys;
                continue;
            }
            foreach ($foreignKeys as $foreignKey) {
                if ($foreignKey['ftable'] == $tableName) {
                    $result['outer'][] = $foreignKey;
                }
            }
        }
        return $result;
    }

    /**
     * Collects the foreign key column details for the given table.
     * @param TableSchema $table the table metadata
     * @return array
     */
    protected static function findConstraints($table)
    {
        $result = [];
        $sql = self::getCreateTableSql($table);

        $regexp = '/CONSTRAINT\s+([^\(^\s]+)\s*FOREIGN KEY\s+\(([^\)]+)\)\s+REFERENCES\s+([^\(^\s]+)\s*\(([^\)]+)\)/mi';
        if (preg_match_all($regexp, $sql, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $fks = array_map('trim', explode(',', str_replace('`', '', $match[2])));
                $pks = array_map('trim', explode(',', str_replace('`', '', $match[4])));
                $index = implode('_', $fks);
                $result[$index] = [
                    'name' => str_replace('`', '', $match[1]),
                    'table' => $table->name,
                    'column' => $fks,
                    'ftable' => str_replace('`', '', $match[3]),
                    'fcolumn' => $pks,
                ];
            }
        }
        return $result;
    }

    /**
     * Gets the CREATE TABLE sql string.
     * @param TableSchema $table the table metadata
     * @return string $sql the result of 'SHOW CREATE TABLE'
     */
    protected static function getCreateTableSql($table)
    {
        $db = Yii::$app->db;
        $row = $db->createCommand('SHOW CREATE TABLE ' . $db->schema->quoteTableName($table->fullName))->queryOne();
        if (isset($row['Create Table'])) {
            $sql = $row['Create Table'];
        } else {
            $row = array_values($row);
            $sql = $row[1];
        }

        return $sql;
    }

}