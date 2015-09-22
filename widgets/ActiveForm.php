<?php
/**
 * Created by PhpStorm.
 * User: pt
 * Date: 22.09.15
 * Time: 16:28
 */

namespace bariew\yii2Tools\widgets;


use yii\helpers\Html;

class ActiveForm extends \yii\widgets\ActiveForm
{
    public $init = true;
    /**
     * Initializes the widget.
     * This renders the form open tag.
     */
    public function init()
    {
        if (!isset($this->options['id'])) {
            $this->options['id'] = $this->getId();
        }
        if ($this->init) {
            echo Html::beginForm($this->action, $this->method, $this->options);
        }
    }
}