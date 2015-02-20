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
use yii\helpers\Html;
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
     * @var array settings for saving image thumbs.
     */
    public $imageSettings = [];
    
    public $files;
    
    protected $fileName = '';
    
    protected $fileNumber = 0;
    
    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_VALIDATE => 'beforeValidate',
            ActiveRecord::EVENT_AFTER_VALIDATE => 'afterValidate',
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
        if (!is_string($this->fileName)) {
            $this->fileName = @$this->owner->oldAttributes[$this->fileField];
        }
        $this->owner->setAttribute($this->fileField, $files);
    }
    
    public function afterValidate()
    {
        $this->files = $this->owner->getAttribute($this->fileField);
        $this->owner->setAttribute($this->fileField, $this->fileName);
    }

    /**
     * Saves attached file and sets db filename field, makes thumbnails.
     */
    public function afterSave()
    {
        if (!$files = $this->files) {
            return true;
        } else if (!is_array($files)) {
            $files = [$files];
        }
        
        $oldFileCount = $this->getFileCount();
        /**
         * @var UploadedFile $file
         */
        foreach ($files as $key => $file) {
            $this->fileNumber = $oldFileCount + $key + 1;
            $this->fileName = $this->fileNumber . '_' . $file->name;
            if ($this->fileNumber == 1) {
                $this->owner->updateAttributes([$this->fileField => $this->fileName]);
            }
            $path = $this->getFilePath(null, $this->fileName);
            $this->createFilePath($path);
            $file->saveAs($path);
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
        foreach ($this->getAllFields() as $field) {
            $path = $this->getFilePath($field, '');
            if (file_exists($path) && is_dir($path)) {
                FileHelper::removeDirectory($path);
            }
        }
    }
    
    protected function getAllFields()
    {
        return array_merge([null], array_keys($this->imageSettings));
    }

    /**
     * Gets file full path.
     * @return bool|mixed|string
     */
    public function getFilePath($field = null, $name = null)
    {
        if ($this->pathCallback) {
            return call_user_func([$this->owner, $this->pathCallback]);
        }
        if (($name === null) && (!$name = $this->getFirstFileName())) {
            return false;
        }
        $storage = is_callable($this->storage)
            ? call_user_func($this->storage) : $this->storage;
        $field = $field ? '_' . preg_replace('/[^-\w]+/', '', $field) : '';
        return \Yii::getAlias(
            $storage 
            . '/' . $this->fileField . $field
            . '/' . preg_replace('/[^\.-\w]+/', '', $name)
        );
    }
    
    public function getFileLink($field = null, $name = null)
    {
        $root = realpath(\Yii::getAlias('@webroot'));
        return str_replace($root, '', $this->getFilePath($field, $name));
    }
    
    public function getFirstFileName()
    {
        return $this->owner->getAttribute($this->fileField);
    }
    
    public function getFileCount()
    {
        if (!$files = $this->getFileList()) {
            return 0;
        }
        $lastName = end($files);
        return preg_match('/^(\d+)_.*$/', $lastName, $matches)
            ? $matches[1] : count($files);
    }
    
    public function getFileList($field = null)
    {
        $dir = $this->getFilePath($field, '');
        if (!file_exists($dir) || !is_dir($dir)) {
            return [];
        }
        return array_diff(scandir($dir), ['.', '..']);
    }

    /**
     * Shows file to the browser.
     * @throws NotFoundHttpException
     */
    public function showFile($field = null, $name = null)
    {
        $file = $this->getFilePath($field, $name);
        if (!file_exists($file) || !is_file($file)) {
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
        \Yii::$app->response->sendFile($file, $name);
    }

    /**
     * Deletes file and all thumbnails by name
     * @param null $name
     * @return bool
     */
    public function deleteFile($name = null)
    {
        foreach ($this->getAllFields() as $field) {
            $path = $this->getFilePath($field, $name);
            if (!$path || !is_file($path) || !file_exists($path)) {
                continue;
            }
            unlink($path);
        }
        if ($name == $this->owner->getAttribute($this->fileField)
           && ($files = $this->getFileList())     
        ) {
            $this->owner->updateAttributes([$this->fileField => reset($files)]);
        }
        return true;
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
     * @param string $field thumbnail name.
     * @param array $options processing options.
     */
    private function processImage($field, $options)
    {
        $originalPath = $this->getFilePath(null, $this->fileName);
        $resultPath = $this->getFilePath($field, $this->fileName);
        $this->createFilePath($resultPath);
        switch ($options['method']) {
            case 'thumbnail' :
                \yii\imagine\Image::thumbnail(
                    $originalPath, $options['width'], $options['height']
                )->save($resultPath, [
                    'format' => pathinfo($this->fileName, \PATHINFO_EXTENSION)
                ]);
                break;
        }
    }

    /**
     * Gets links for all model files.
     * @param null $field
     * @return array
     */
    public function linkList($field = null)
    {
        $result = [];
        foreach ($this->getFileList() as $path) {
            $name = basename($path);
            $result[$name] = $this->getFileLink($field, basename($path));
        }
        return $result;
    }
}