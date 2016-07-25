<?php
/**
 * CaptchaBehavior class file.
 * @copyright (c) 2016, Pavel Bariev
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace bariew\yii2Tools\behaviors;

use bariew\yii2Tools\validators\CaptchaValidator;
use Yii;
use yii\base\Behavior;
use yii\captcha\Captcha;
use yii\captcha\CaptchaAction;
use yii\db\ActiveRecord;
use yii\validators\RequiredValidator;


/**
 * @property ActiveRecord $owner
 *
 * @author Pavel Bariev <bariew@yandex.ru>
 */
class CaptchaBehavior extends Behavior
{
    const REQUEST_VALUE = 'behaviorRequest';
    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_INIT => 'afterInit',
            ActiveRecord::EVENT_BEFORE_VALIDATE => 'beforeValidate',
        ];
    }

    public function afterInit()
    {
        if (!Yii::$app->request instanceof \yii\web\Request) {
            return;
        }

        if ($this->owner->load(Yii::$app->request->get())
            && @Yii::$app->request->get($this->owner->formName())['captcha'] == static::REQUEST_VALUE
        ) {
            ob_end_clean();
            echo (new CaptchaAction('captcha', Yii::$app->controller))->run();
            Yii::$app->end();
        }
    }
    /**
     */
    public function beforeValidate()
    {
        if (!Yii::$app->request instanceof \yii\web\Request) {
            return;
        }
        $this->setCaptcha(@Yii::$app->request->post($this->owner->formName())['captcha']);
        (new RequiredValidator())->validateAttribute($this->owner, 'captcha');
        (new CaptchaValidator())->validateAttribute($this->owner, 'captcha');
    }

    protected $_captcha;
    public function getCaptcha()
    {
        return $this->_captcha;
    }

    public function setCaptcha($value)
    {
        $this->_captcha = $value;
    }

    /**
     * @param \yii\widgets\ActiveForm|null $form
     * @return string
     * @throws \Exception
     */
    public function getCaptchaWidget($form = null)
    {
        $options = [
            'captchaAction' => $this->getCaptchaAction(),
            'model' => $this->owner,
            'attribute' => 'captcha',
        ];
        return $form
            ? $form->field($this->owner, 'captcha')->widget(Captcha::className(), $options)
            : Captcha::widget($options);
    }

    protected function getCaptchaAction()
    {
        return [
            '/'.Yii::$app->request->pathInfo,
            $this->owner->formName().'[captcha]' => static::REQUEST_VALUE,
        ];
    }
}