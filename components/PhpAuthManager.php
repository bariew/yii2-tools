<?php
/**
 * PhpAuthManager class file.
 * @copyright (c) 2016, Pavel Bariev
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace bariew\yii2Tools\components;
use bariew\yii2Tools\helpers\HtmlHelper;
use Yii;
use yii\base\Event;
use yii\base\ViewEvent;
use yii\web\Application;
use yii\web\Controller;
use yii\web\HttpException;
use yii\web\Response;

/**
 * Description.
 * Php rbac manager with url access support. It raises event for current web controller
 * and checks whether there is a rbac access permission named like <module>/<controller>/<action> for the current user.
 * It also supports regexps for permission names like <module>/\w+/\w+
 *
 * Usage:
 * 1. add to your config file components:
   'components' => [
   ...
        'authManager'   => [
            'class' => 'bariew\yii2Tools\components\PhpAuthManager',
            'defaultRoles' => ['app/site/.*', 'user/default/.*', 'page/default/.*'], // everyone can access these urls (app is for base controllers)
        ],
   ]
 * 2. add it to your config bootstrap for auto init the web access event :
    'bootstrap' => [... , 'authManager']
 *
 * @author Pavel Bariev <bariew@yandex.ru>
 *
 */
class PhpAuthManager extends \yii\rbac\PhpManager
{
    /** @var bool  */
    public $removeDeniedLinks = false;

    /**
     * @inheritdoc
     */
    public $itemFile = '@app/rbac/items.php';
    /**
     * @inheritdoc
     */
    public $assignmentFile = '@app/rbac/assignments.php';
    /**
     * @inheritdoc
     */
    public $ruleFile = '@app/rbac/rules.php';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if(!Yii::$app instanceof Application){
            return;
        }
        Event::on(Controller::className(), 'beforeAction', [$this, 'beforeActionAccess']);
        if ($this->removeDeniedLinks) {
            Event::on(Response::className(), 'afterPrepare', [$this, 'responseAfterPrepare']);
        }
    }

    /**
     * @inheritdoc
     */
    public function checkAccess($userId, $permissionName, $params = [])
    {
        $permissionName = preg_replace('#^\/(.*)#', '$1', $permissionName);
        foreach ($this->getPermissions() as $permission) {
            if ($permission->type == $permission::TYPE_ROLE) {
                continue;
            }
            if (!preg_match('#^'.$permission->name.'$#', $permissionName)) {
                continue;
            }
            if (parent::checkAccess($userId, $permission->name, $params)) {
                return true;
            }
        }
        return parent::checkAccess($userId, $permissionName, $params);
    }

    /**
     * Checks whether current user has access to current controller action.
     * @param Event $event controller beforeAction event.
     * @throws \yii\web\HttpException
     */
    public function beforeActionAccess(Event $event)
    {
        $controller = $event->sender;
        if (!Yii::$app->user->can($controller->module->id.'/'.$controller->id.'/'.$controller->action->id)) {
            throw new HttpException(403, Yii::t('app', 'Access denied'));
        }
    }

    /**
     * Runs access methods for view event.
     * @param Event $event view event.
     * @return bool
     */
    public function responseAfterPrepare(Event $event)
    {
        if (!Yii::$app instanceof Application) {
            return true;
        }

        $doc = \phpQuery::newDocumentHTML($event->sender->content);
        foreach ($doc->find('a') as $el) {
            $link = pq($el);
            if (!$rule = HtmlHelper::urlToPath($link->attr('href'))) {
                continue;
            }
            if (!$this->checkAccess(Yii::$app->user->id, implode('/', $rule))) {
                $link->remove();
            }
        }

        foreach ($doc->find('ul.dropdown-menu') as $el) {
            $ul = pq($el);
            if (!$ul->find('a[href!="#"]')->length) {
                $ul->parent('li.dropdown')->addClass('hide');
            }
        }
        $event->sender->content = $doc;
    }
}