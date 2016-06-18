<?php
/**
 * ModuleMenu class file.
 * @copyright (c) 2016, Pavel Bariev
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace bariew\yii2Tools\widgets;
use Yii;
use bariew\dropdown\Nav;
use yii\helpers\ArrayHelper;

/**
 * For rendering default module menu if it is defined in module params['menu'].
 *
 * @example
<?php
    \yii\bootstrap\NavBar::begin([
        'brandLabel' => Yii::$app->name,
        'brandUrl' => Yii::$app->homeUrl,
        'options' => [
            'class' => 'navbar-inverse navbar-fixed-top',
            'style' => 'z-index: 9999;'
        ],
    ]);
    echo \bariew\yii2Tools\widgets\ModuleMenu::widget([
        'direction' => 'left',
        'options' => ['class' => 'navbar-nav navbar-right']
    ]);
    NavBar::end();
?>
 * @author Pavel Bariev <bariew@yandex.ru>
 *
 *
 */
class ModuleMenu extends Nav
{
    protected function createItems($items)
    {
        $result = [];
        foreach (\Yii::$app->modules as $id => $options) {
            $module = Yii::$app->getModule($id);
            $params = $module->params;
            if (!isset($params['menu']) || !isset($params['menu']['label'])) {
                continue;
            }

            if (isset($result[$params['menu']['label']]) && ($oldItem = $result[$params['menu']['label']])) {
                $oldItems = isset($oldItem['items']) ? $oldItem['items'] : $oldItem;
                $newItems = isset($params['menu']['items']) ? $params['menu']['items'] : $params['menu'];
                $params['menu']['items'] = ArrayHelper::merge($oldItems, $newItems);
            }
            $result[$params['menu']['label']] = $params['menu'];
        }
        array_walk($result, function($v, $k) use(&$result) { if(!$v) unset($result[$k]);});
        return array_values($result);
    }
}
