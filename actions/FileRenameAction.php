<?php
/**
 * FileDeleteAction class file.
 * @copyright (c) 2015, Pavel Bariev
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace bariew\yii2Tools\actions;
use yii\base\Action;
use Yii;

/**
 * See README
 *
 * @author Pavel Bariev <bariew@yandex.ru>
 */
class FileRenameAction extends Action
{
    
    /**
     * Deletes file and thumbnails.
     * @param integer $id owner Item model id.
     * @param string $name file name.
     * @return \yii\web\Response
     */
    public function run($id, $name)
    {
        $model = $this->controller->findModel($id);
        if (
            $name
            && ($newName = Yii::$app->request->post('newName'))
            && $newName != $name
            && $model->renameFile($name, $newName)
        ) {
            Yii::$app->session->setFlash('success', Yii::t('app', 'File successfully renamed'));
        } else {
            Yii::$app->session->setFlash('error', Yii::t('app', 'File rename error'));
        }
        return $this->controller->redirect(Yii::$app->request->referrer);
    }
}
