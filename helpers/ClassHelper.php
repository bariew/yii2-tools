<?php
/**
 * ClassHelper class file.
 * @copyright (c) 2016, Pavel Bariev
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace bariew\yii2Tools\helpers;

use \yii\helpers\BaseFileHelper;
use \Yii;

/**
 * Description.
 *
 * Usage:
 * @author Pavel Bariev <bariew@yandex.ru>
 *
 */
class ClassHelper
{
    public static function getEventNames($className)
    {
        $result = [];
        $reflection = new \ReflectionClass($className);
        foreach ($reflection->getConstants() as $name => $value) {
            if (!preg_match('/^EVENT/', $name)) {
                continue;
            }
            $result[$name] = $value;
        }
        return $result;
    }

    public static function getAllClasses()
    {
        $result = [];
        foreach (self::getAllAliases() as $alias) {
            $path = \Yii::getAlias($alias);
            $files = is_dir($path) ? BaseFileHelper::findFiles($path) : [$path];
            foreach ($files as $filePath) {
                if (!preg_match('/.*\/[A-Z]\w+\.php/', $filePath)) {
                    continue;
                }
                $className = str_replace([$path, '.php', '/', '@'], [$alias, '', '\\', ''], $filePath);
                $result[] = $className;
            }
        }
        return $result;
    }

    public static function getAllAliases()
    {
        $result = [];
        foreach (Yii::$aliases as $aliases) {
            foreach (array_keys((array) $aliases) as $alias) {
                if (!$alias) {
                    continue;
                }
                $result[]  = $alias;
            }
        }
        return $result;
    }
}