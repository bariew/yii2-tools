<?php
/**
 * ArrayField class file.
 * @copyright (c) 2016, Pavel Bariev
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace bariew\yii2Tools\widgets;


use yii\helpers\Html;
use yii\widgets\InputWidget;

/**
 * Description.
 *
 * Usage:
 * @author Pavel Bariev <bariew@yandex.ru>
 *
 */
class ArrayField extends InputWidget
{
    public $labels = [];
    public function run()
    {
        $result = [];
        $fields = $this->model->{$this->attribute};
        $template =  '{label}<div class="input-group">{input}<div class="input-group-addon">'
            .Html::tag('em', '', [
            'class' => 'glyphicon glyphicon-trash',
            'style' => 'cursor:pointer',
            'onclick' => '$(this).closest(".form-group").fadeOut().remove();'
        ]).'</div></div>{error}';
        foreach ($fields as $key => $value) {
            $label = @$this->labels[$key] ? : $key;
            $result[] = (new ActiveForm())
                ->field($this->model, $this->attribute . "[{$key}]", compact('template'))
                ->label($label)
                ->textInput(['placeholder' => $label]);
        }
        $result[] = "<div class='array-input add input-group'>"
            . Html::textInput('add', null, array_merge(
                ['class' => 'form-control', 'placeholder' => \Yii::t('app', 'Add field')], $this->options)
            )
            . '<div class="input-group-addon">'
            .  Html::tag('em', '', [
                'class' => 'glyphicon glyphicon-plus',
                'style' => 'cursor:pointer',
                'template' => (new ActiveForm())->field($this->model, $this->attribute . "[{{key}}]", [
                    'template' => $template,
                ])->label('{{key}}')->textInput(['placeholder' => '{{key}}']),
                'onclick' => 'var $key = $(this).parent().prev().val(); if (!$key) return;
                    $(this).parents(".add").before($(this).attr("template").replace(/{{key}}/g, $key));'
            ])
            ."</div></div>";
        return implode('', $result);
    }
}