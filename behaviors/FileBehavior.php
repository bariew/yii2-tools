<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace bariew\yii2Tools\behaviors;

use yii\db\ActiveRecord;
use yii\base\Behavior;
use yii\helpers\FileHelper;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use \yii\helpers\HtmlPurifier;

/**
 * This is for saving model file.
 * It takes uploaded file from owner $fileField attribute
 * moves it to custom path and updates owner $fileField in db which is just alias
 * for real file name.
 * Real file name and path is constant for owner like /web/files/{ownerClassName}/{owner_id}
 * You can define your own method for file path.
 * You can also get file with this->showFile() or this->sendFile() method.
 *
 * Usage:
 * Define this behavior in your ActiveRecord instance class.
    public function behaviors() 
    {
        return [
            'fileBehavior' => [
                'class' => \bariew\yii2Tools\FileBehavior::className(),
                'fileField' => 'image',
                'imageSettings' => [
                    'thumb1' => ['method' => 'thumbnail', 'width' => 50, 'height' => 50],
                    'thumb2' => ['method' => 'thumbnail', 'width' => 100, 'height' => 100],
                    'thumb3' => ['method' => 'thumbnail', 'width' => 200, 'height' => 200],
                ]
            ]
        ];
    }
 *
 *
 *
 * @author Pavel Bariev <bariew@yandex.ru>
 *
 * @property ActiveRecord $owner
 */
class FileBehavior extends Behavior
{
    /**
     * @var string base path for all files.
     */
    public $storage = '@app/web/files';

    /**
     * @var string owner required uploaded file field name.
     */
    public $fileField;

    /**
     * @var string optional owner filePath naming method. By default we use inner $this->getFilePath() method.
     */
    public $pathCallback;

    /**
     * @var type array settings for saving image thumbs.
     */
    public $imageSettings = [];
    
    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_VALIDATE => 'beforeValidate',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete'
        ];
    }

    /**
     * Attaches uploaded file to owner.
     */
    public function beforeValidate()
    {
        if (!$file = UploadedFile::getInstance($this->owner, $this->fileField)) {
            return true;
        }
        $this->owner->setAttribute($this->fileField, $file);
    }

    /**
     * Saves attached file and sets db filename field, makes thumbnails.
     */
    public function afterSave()
    {
        if (!$file = $this->owner->getAttribute($this->fileField)) {
            return true;
        }
        $this->owner->updateAttributes([
            $this->fileField => HtmlPurifier::process($file->name)
        ]);
        $path = $this->getFilePath();
        $this->createFilePath($path);
        $file->saveAs($path);
        foreach ($this->imageSettings as $name => $options) {
            $this->processImage($name, $options);
        }
    }

    /**
     * Removes owner files.
     */
    public function afterDelete()
    {
        $names = array_merge([null], array_keys($this->imageSettings));
        foreach ($names as $name) {
            $path = $this->getFilePath($name);
            if (file_exists($path) && is_file($path)) {
                unlink($path);
            }
        }
    }

    /**
     * Gets file full path.
     * @return bool|mixed|string
     */
    public function getFilePath($field = null)
    {
        if ($this->pathCallback) {
            return call_user_func([$this->owner, $this->pathCallback]);
        }
        return \Yii::getAlias(
            $this->storage 
            . '/' . $this->owner->primaryKey 
            . '_' . ($field == null ? $this->fileField : $field)
        );
    }

    /**
     * Shows file to the browser.
     * @throws NotFoundHttpException
     */
    public function showFile($field = null)
    {
        $file = $this->getFilePath($field);
        if (!file_exists($file)) {
            throw new NotFoundHttpException;
        }
        header('Content-Type: '. FileHelper::getMimeType($file), true);
        header('Content-Length: ' . filesize($file));
        readfile($file);
    }

    /**
     * Sends file to user download.
     * @throws NotFoundHttpException
     */
    public function sendFile($field = null)
    {
        $file = $this->getFilePath($field);
        if (!file_exists($file)) {
            throw new NotFoundHttpException;
        }
        \Yii::$app->response->sendFile(
            $file, $this->owner->getAttribute($this->fileField));
    }

    /**
     * Generates path recursively.
     * @param string $path path to create.
     * @return bool
     */
    private function createFilePath($path)
    {
        $dir = dirname($path);
        return file_exists($dir) || FileHelper::createDirectory($dir, 0775, true);
    }
    
    /**
     * Creates image copies processed with options
     * @param string $name thumbnail name.
     * @param array $options processing options.
     */
    private function processImage($name, $options)
    {
        $originalPath = $this->getFilePath();
        $resultPath = $this->getFilePath($name);
        switch ($options['method']) {
            case 'thumbnail' :
                \yii\imagine\Image::thumbnail(
                    $originalPath, $options['width'], $options['height']
                )->save($resultPath, [
                    'format' => pathinfo($this->owner->getAttribute($this->fileField), \PATHINFO_EXTENSION)
                ]);
                break;
        }
    }
}