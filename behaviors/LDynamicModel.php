<?php
/**
 * LDynamicModel class file.
 * @copyright (c) 2015, Pavel Bariev
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace bariew\yii2Tools\behaviors;

use yii\base\DynamicModel;
use yii\base\InvalidConfigException;
use yii\validators\Validator;

/**
 * Extends standard dynamic model for scenarios functionality.
 *
 * Usage: use its validateModelData method with original model (with scenario set)
 * @author Pavel Bariev <bariew@yandex.ru>
 * @see https://github.com/yiisoft/yii2/issues/10007
 */
class LDynamicModel extends DynamicModel
{
    /**
     * @var \yii\db\ActiveRecord
     */
    public $origin;

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        return $this->origin ? $this->origin->scenarios() : parent::scenarios();
    }

    /**
     * @inheritdoc
     */
    public function getScenario()
    {
        return $this->origin ? $this->origin->scenario : parent::getScenario();
    }

    /**
     * @param array $data
     * @param array $rules
     * @param \yii\db\ActiveRecord|boolean $origin
     * @return DynamicModel
     * @throws InvalidConfigException
     * @see DynamicModel::validateData
     */
    public static function validateModelData(array $data, $rules = [], $origin = false)
    {
        $model = new static($data);
        $model->origin = $origin;
        $model->setScenario($origin->scenario);
        if (!empty($rules)) {
            $validators = $model->getValidators();
            foreach ($rules as $rule) {
                if ($rule instanceof Validator) {
                    $validators->append($rule);
                } elseif (is_array($rule) && isset($rule[0], $rule[1])) { // attributes, validator type
                    $validator = Validator::createValidator($rule[1], $model, (array) $rule[0], array_slice($rule, 2));
                    $validators->append($validator);
                } else {
                    throw new InvalidConfigException('Invalid validation rule: a rule must specify both attribute names and validator type.');
                }
            }
        }

        $model->validate();

        return $model;
    }
}