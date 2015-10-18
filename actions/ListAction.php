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
use yii\web\Response;
use yii\db\ActiveRecord;

class ListAction extends Action
{
    public $listAttribute;
    public $postAttributes = [];

    public function run()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        /** @var ActiveRecord $model */
        $model = $this->controller->findModel(false);
        $model->attributes = compact($this->postAttributes, Yii::$app->request->post('depdrop_parents'));
        $method = GridHelper::listName($this->listAttribute);
        $items = [];
        foreach ($model->$method() as $id => $name) {
            $items[] = compact('id', 'name');
        }

        return ['output' => $items, 'selected' => ''];
    }
}