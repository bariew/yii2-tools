<?php
/**
 * Created by PhpStorm.
 * User: pt
 * Date: 25.07.16
 * Time: 21:26
 */

namespace bariew\yii2Tools\validators;


use yii\captcha\CaptchaAction;

class CaptchaValidator extends \yii\captcha\CaptchaValidator
{
    public $actionConfig = [];
    /**
     * @inheritdoc
     */
    public function createCaptchaAction()
    {
        return (new CaptchaAction('captcha', \Yii::$app->controller, $this->actionConfig));
    }
}