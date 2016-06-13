<?php
/**
 * Created by PhpStorm.
 * User: pt
 * Date: 07.06.16
 * Time: 11:57
 */

//namespace bariew\yii2Tools\helpers;


//use yii\helpers\FileHelper;

class RefactorHelper
{
    /**
     * @param $from
     * @param $to
     * @param $path
     * @example php vendor/bariew/yii2-tools/helpers/RefactorHelper.php replace "Yii::t\(\'admin_view\'" "Yii::t('admin_{folder}_{file}'" /var/www/webdin/application/modules/admin/views/
     * - replaces all Yii::t('admin_view' appearances with Yii::t('admin_{folder}_{file}' from path variables
     */
    public static function replace($from, $to, $path)
    {
        foreach (static::findFiles($path) as $file) {
            if (!is_file($file)) {
                continue;
            }
            $to1 = str_replace(
                ['{folder}', '{file}'],
                [basename(dirname($file)), strtolower(basename($file, '.php'))],
                $to
            );
            file_put_contents($file, preg_replace("#{$from}#", $to1, file_get_contents($file)));
        }

    }

    public static function findFiles($path)
    {
        if (is_file($path)) {
            return [$path];
        }
        $it = new RecursiveDirectoryIterator($path);
        return new RecursiveIteratorIterator($it);
    }
}
$path = array_shift($argv);
$method = array_shift($argv);
call_user_func_array(['RefactorHelper', $method], $argv);