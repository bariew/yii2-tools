<?php
/**
 * Created by PhpStorm.
 * User: pt
 * Date: 06.08.15
 * Time: 17:58
 */

namespace bariew\yii2Tools\helpers;
use yii\helpers\Html;

class HtmlHelper
{
    public static function submitDropdown($model, $attribute, $items, $content = "Save", $options = [])
    {
        if (count($items) == 1) {
            return Html::hiddenInput(Html::getInputName($model, $attribute), key($items))
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
                . Html::activeHiddenInput($model, $attribute)
                . Html::button($content . '<span class="caret"></span>',
                    ['class' => 'btn dropdown-toggle btn-primary', "data-toggle"=>"dropdown"])
                . Html::beginTag('ul', ['class'=>'dropdown-menu'])
                    . $lis
                . Html::endTag('ul')
            . Html::endTag('div');
    }
}