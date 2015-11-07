<?php
/**
 * Created by PhpStorm.
 * User: pt
 * Date: 18.10.15
 * Time: 12:52
 */

namespace bariew\yii2Tools\actions;


use bariew\yii2Tools\helpers\GridHelper;
use yii\base\Action;
use Yii;
use yii\helpers\Html;
use yii\web\Response;
use yii\db\ActiveRecord;

class ListAction extends Action
{
    const RESPONSE_DEPDROP = 'DepDrop';
    const RESPONSE_HTML = 'html';

    public $listAttribute;
    public $postAttributes = [];
    public $response = self::RESPONSE_DEPDROP;

    public function run()
    {
        switch($this->response) {
            case static::RESPONSE_DEPDROP:
                $post = Yii::$app->request->post('depdrop_parents');
                break;
            case static::RESPONSE_HTML:
                $post = Yii::$app->request->post();
                break;
        }
        /** @var ActiveRecord $model */
        $model = $this->controller->findModel(false);
        $model->setAttributes(array_combine($this->postAttributes, array_values($post)));
        $method = GridHelper::listName($this->listAttribute);
        $list = $model->$method();
        switch($this->response) {
            case static::RESPONSE_DEPDROP:
                Yii::$app->response->format = Response::FORMAT_JSON;
                $items = [];
                foreach ($list as $id => $name) {
                    $items[] = compact('id', 'name');
                }
                return ['output' => $items, 'selected' => ''];
                break;
            case static::RESPONSE_HTML:
                return Html::activeDropDownList($model, $this->listAttribute, $list);
                break;
        }
    }
}