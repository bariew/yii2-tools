<?php
/**
 * Created by PhpStorm.
 * User: pt
 * Date: 10.06.16
 * Time: 13:28
 */

namespace bariew\yii2Tools\web;


use yii\web\UrlRule;

class RedirectUrlRule extends UrlRule
{
    public $redirectUrl;
    /**
     * @inheritdoc
     */
    public function parseRequest($manager, $request)
    {
        if ($result = parent::parseRequest($manager, $request)) {
            $this->redirectUrl = $this->redirectUrl ? : \Yii::$app->homeUrl;
            \Yii::$app->response->redirect($this->redirectUrl) && \Yii::$app->end();
        }
        return $result;
    }
}