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
 * For multiple upload just follow common rules (set rules maxFiles, set input name[] and set input multiple=>true):
 * @see https://github.com/yiisoft/yii2/blob/master/docs/guide/input-file-upload.md
 *
 *
 * @author Pavel Bariev <bariew@yandex.ru>
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
    
    protected $fileName = '';
    
    protected $fileNumber = 0;
    
    
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
        if (
            (!$files = UploadedFile::getInstance($this->owner, $this->fileField))
            && (!$files = UploadedFile::getInstances($this->owner, $this->fileField))     
        ) {
            return true;
        }
        $this->fileName = $this->owner->getAttribute($this->fileField);
        $this->owner->setAttribute($this->fileField, $files);
    }

    
    /**
     * Saves attached file and sets db filename field, makes thumbnails.
     */
    public function afterSave()
    {
        
        $files = $this->owner->getAttribute($this->fileField);
        if (!$files) {
            return true;
        } else if (!is_array($files)) {
            $files = [$files];
        }
        
        $oldFileCount = $this->getFileCount();
        foreach ($files as $key => $file) {
            $path = $this->getFilePath();
            $this->createFilePath($path);
            $this->fileNumber = $oldFileCount + $key;
            $this->fileName = $this->fileNumber . '_' . HtmlPurifier::process($file->name);
            if ($this->fileNumber == 0) {
                $this->owner->updateAttributes([$this->fileField => $this->fileName]);
            }
            $file->saveAs($path . $this->fileName);
            foreach ($this->imageSettings as $name => $options) {
                $this->processImage($name, $options);
            }    
        }
    }

    /**
     * Removes owner files.
     */
    public function afterDelete()
    {
        $fields = array_merge([null], array_keys($this->imageSettings));
        foreach ($fields as $field) {
            $path = $this->getFilePath($field);
            if (file_exists($path) && is_dir($path)) {
                FileHelper::removeDirectory($path);
            }
        }
    }

    /**
     * Gets file full path.
     * @return bool|mixed|string
     */
    public function getFilePath($field = null, $name = '')
    {
        if ($this->pathCallback) {
            return call_user_func([$this->owner, $this->pathCallback]);
        }
        return \Yii::getAlias(
            $this->storage 
            . '/' . $this->owner->primaryKey 
            . '/' . ($field 
                ? preg_replace('/[^-\w+]/', '', $field) 
                : $this->fileField)
            . '/' . $name
        );
    }
    
    public function getFirstFileName()
    {
        return $this->owner->getAttribute($this->fileField);
    }
    
    public function getFileCount()
    {
        return count($this->getFileList());
    }
    
    public function getFileList($field = null)
    {
        $path = $this->getFilePath($field);
        if (!file_exists($path) || !is_dir($path)) {
            return [];
        }
        return array_diff(scandir($path), ['.', '..']);
    }

    /**
     * Shows file to the browser.
     * @throws NotFoundHttpException
     */
    public function showFile($field = null, $name = null)
    {
        if (!$name && (!$name = $this->getFirstFileName())) {
            return false;
        }
        $file = $this->getFilePath($field, $name);
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
    public function sendFile($field = null, $name = null)
    {
        if (!$name && (!$name = $this->getFirstFileName())) {
            return false;
        }
        $file = $this->getFilePath($field, $name);
        if (!$name || !file_exists($file)) {
            throw new NotFoundHttpException;
        }
        \Yii::$app->response->sendFile(
            $file, $name);
    }

    /**
     * Generates path recursively.
     * @param string $path path to create.
     * @return bool
     */
    private function createFilePath($path)
    {
        return file_exists($path) || FileHelper::createDirectory($path, 0775, true);
    }
    
    /**
     * Creates image copies processed with options
     * @param string $field thumbnail name.
     * @param array $options processing options.
     */
    private function processImage($field, $options)
    {
        $originalPath = $this->getFilePath() . $this->fileName;
        $resultPath = $this->getFilePath($field);
        $this->createFilePath($resultPath);
        switch ($options['method']) {
            case 'thumbnail' :
                \yii\imagine\Image::thumbnail(
                    $originalPath, $options['width'], $options['height']
                )->save($resultPath . $this->fileName, [
                    'format' => pathinfo($this->fileName, \PATHINFO_EXTENSION)
                ]);
                break;
        }
    }
}