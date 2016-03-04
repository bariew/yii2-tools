<?php
/**
 * ListValidator class file.
 * @copyright (c) 2015, Pavel Bariev
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace bariew\yii2Tools\validators;

use yii\helpers\Inflector;
use yii\validators\Validator;

/**
 * See README
 *
 * @author Pavel Bariev <bariew@yandex.ru>
 *
 */
class JsonValidator extends Validator
{
    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        if (is_array($model->$attribute)) {
            return;
        }
        json_decode($model->$attribute);
        if (json_last_error()) {
            $this->addError($model, $attribute, ($this->message ? : json_last_error_msg()));
        }
    }
}