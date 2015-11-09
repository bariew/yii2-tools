<?php
/**
 * UrlView class file.
 * @copyright (c) 2015, bariew
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace bariew\yii2Tools\widgets;

/**
 * This widget just runs app controller action an returns its response.
 * You may, for example, use it with rbac to deny rendering some parts of view.
 *
 * @author Pavel Bariev <bariew@yandex.ru>
 */
class UrlView extends \yii\base\Widget
{
    public $params = [];
    public $url;

    /**
     * @inheritdoc
     */
    public function run()
    {
        try {
            $response = \Yii::$app->runAction($this->url, $this->params);
        } catch (\Exception $e) {
            return false;
        }
        return $response;
    }
}
