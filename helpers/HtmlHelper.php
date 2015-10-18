<?php
/**
 * Created by PhpStorm.
 * User: pt
 * Date: 06.08.15
 * Time: 17:58
 */

namespace bariew\yii2Tools\helpers;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

class HtmlHelper
{
    public static function submitDropdown(ActiveForm $form, $model, $attribute, $items, $content = "Save", $options = [])
    {
        if (count($items) == 1) {
            return $form->field($model, $attribute, ['options'=>['class' => 'pull-left']])
                ->label(false)->hiddenInput(['value' => key($items)])
                . Html::submitButton(reset($items), $options);
        }
        $lis = '';
        foreach ($items as $key => $name) {
            $lis .= Html::tag('li', "<a href='#'>$name</a>", ['onclick' => "
                $(this).closest('div').find('input').val('{$key}');
                $(this).parents('form').submit();
            "]);
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