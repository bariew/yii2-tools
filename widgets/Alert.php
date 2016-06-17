<?php
/**
 * Alert class file.
 * @copyright (c) 2016, Pavel Bariev
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace bariew\yii2Tools\widgets;
use yii\base\Widget;

/**
 * Renders app flashes.
 *
 * Usage:
 * @author Pavel Bariev <bariew@yandex.ru>
 *
 */
class Alert extends Widget
{
    public function run()
    {
        $result = [];
        foreach(\Yii::$app->session->getAllFlashes() as $key=>$message) {
            $result[] = \yii\bootstrap\Alert::widget([
                'options' => ['class' => 'alert-'.($key == 'error' ? 'danger' : $key)],
                'body' => implode("<hr />", (array) $message),
            ]);
        }

        return implode('', $result);
    }

}