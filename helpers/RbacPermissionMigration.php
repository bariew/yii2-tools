<?php
/**
 * RbacPermissionMigration class file.
 * @copyright (c) 2015, Pavel Bariev
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace bariew\yii2Tools\helpers;


use yii\db\Query;
use yii\db\Migration;
use yii\rbac\Role;

/**
 * Parent class for rbac access adding migrations.
 *
 * Usage:
 * Extend this class from your migration and define its $permissions like
 * ['roleName'=>['addedPermissionName']]
 *
 * @author Pavel Bariev <bariew@yandex.ru>
 *
 */
class RbacPermissionMigration extends Migration
{
    protected $autItemTable = 'auth_item';
    protected $authItemChildTable = 'auth_item_child';
    protected $permissions = [
        'default' => [],
        'guest' => []
    ];

    /**
     * @inheritdoc
     */
    public function up()
    {
        $allPermissions = [];
        foreach ($this->permissions as $role => $permissions) {
            if (!$permissions) {
                continue;
            }
            $allPermissions = array_merge(
                $allPermissions,
                (new Query())->from($this->autItemTable)->select('name')->where([
                    'type' => Role::TYPE_PERMISSION,
                    'name' => $permissions
                ])->column()
            );
            if ($newPermissions = array_diff($permissions, $allPermissions)) {
                $this->batchInsert(
                    $this->autItemTable,
                    ['name', 'type'],
                    MigrationHelper::toBatchData($newPermissions, [Role::TYPE_PERMISSION])
                );
            }
            $allPermissions = array_merge($allPermissions, $newPermissions);
            $this->batchInsert(
                $this->authItemChildTable,
                ['child', 'parent'],
                MigrationHelper::toBatchData($permissions, [$role])
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $allPermissions = [];
        foreach ($this->permissions as $role => $permissions) {
            if (!$permissions) {
                continue;
            }
            $allPermissions = array_merge($allPermissions, $permissions);
            $this->delete('auth_item_child', ['child' => $permissions, 'parent' => $role]);
        }
        $deletePermissions = array_diff(
            $allPermissions,
            (new Query())->from($this->authItemChildTable)
                ->where(['child' => $allPermissions])->select('child')->column()
        );
        if ($deletePermissions) {
            $this->delete($this->autItemTable, ['name' => $deletePermissions, 'type' => Role::TYPE_PERMISSION]);
        }
    }
}