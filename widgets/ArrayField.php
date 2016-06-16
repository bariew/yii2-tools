<?php
/**
 * Created by PhpStorm.
 * User: pt
 * Date: 16.06.16
 * Time: 22:51
 */

namespace bariew\yii2Tools\widgets;


use yii\helpers\Html;
use yii\widgets\InputWidget;

class ArrayField extends InputWidget
{
    public function run()
    {
        $result = [];
        $fields = $this->model->{$this->attribute};
        $name = $this->model->formName() . "[{$this->attribute}][{{key}}]";
        $template = "<div class='array-input'>"
            . Html::textInput($name, '{{value}}', array_merge($this->options, ['placeholder' => '{{value}}']))
            . ' ' . Html::tag('em', '', [
                'class' => 'glyphicon glyphicon-trash',
                'style' => 'cursor:pointer',
                'onclick' => '$(this).parent().fadeOut().remove();'
            ])
               . '<div class="clearfix"></div>'
            . "</div>";
        foreach ($fields as $key => $value) {
            $result[] = str_replace(['{{key}}', '{{value}}'], [$key, $value], $template);
        }
        $result[] = "<div class='array-input add'>"
            . Html::textInput('add')
            . Html::button(\Yii::t('app', 'Add'), [
                'class' => 'btn btn-default',
                'template' => $template,
                'onclick' => 'var $key = $(this).prev().val(); if (!$key) return;
                    $(this).parent().before($(this).attr("template").replace("{{key}}", $key).replace("{{value}}", $key));'
            ]) ."</div>";
        return implode('', $result);
    }
}