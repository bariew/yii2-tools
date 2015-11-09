<?php
/**
 * ActiveForm class file.
 * @copyright (c) 2015, Pavel Bariev
 * @license http://www.opensource.org/licenses/bsd-license.php
 */
namespace bariew\yii2Tools\widgets;


use yii\helpers\Html;

/**
 * This active form will not echo form beginning if you don't want.
 * For using ActiveForm functionality without echo.
 *
 * @author Pavel Bariev <bariew@yandex.ru>
 *
 */
class ActiveForm extends \yii\widgets\ActiveForm
{
    /**
     * @var bool whether to echo form tag.
     */
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