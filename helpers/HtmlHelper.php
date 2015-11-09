<?php
/**
 * HtmlHelper class file.
 * @copyright (c) 2015, Pavel Bariev
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace bariew\yii2Tools\helpers;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * See README
 *
 * @author Pavel Bariev <bariew@yandex.ru>
 *
 */
class HtmlHelper
{
    /**
     * Button with multiple submit options.
     * @param ActiveForm $form
     * @param $model
     * @param $attribute
     * @param $items
     * @param string $content
     * @param array $options
     * @return string
     */
    public static function submitDropdown(ActiveForm $form, $model, $attribute, $items, $content = "Save", $options = [])
    {
        if (count($items) == 1) {
            return $form->field($model, $attribute, ['options'=>['class' => 'pull-left']])
                ->label(false)->hiddenInput(['value' => key($items)])
                . Html::submitButton(reset($items), $options);
        }
        $lis = '';
        foreach ($items as $key => $name) {
            $lis .= Html::tag('li', "<a href='#'>$name</a>", [
                'onclick' => "$(this).closest('div').find('input').val('{$key}');
                    $(this).parents('form').submit();",
                'class' => $model->$attribute == $key ? 'active' : ''
            ]);
        }

        return Html::beginTag('div', ['class' => 'btn-group'])
                . $form->field($model, $attribute, ['options'=>['class' => 'pull-left']])->label(false)->hiddenInput()
                . Html::button($content . '<span class="caret"></span>',
                    ['class' => 'btn dropdown-toggle btn-primary', "data-toggle"=>"dropdown"])
                . Html::beginTag('ul', ['class'=>'dropdown-menu'])
                    . $lis
                . Html::endTag('ul')
            . Html::endTag('div');
    }
}