<?php
/**
 * HtmlHelper class file.
 * @copyright (c) 2015, Pavel Bariev
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace bariew\yii2Tools\helpers;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * See README
 *
 * @author Pavel Bariev <bariew@yandex.ru>
 *
 */
class HtmlHelper
{
    /**
     * Csrf input for submitting form data
     * @return string
     */
    public static function csrfInput()
    {
        return Html::hiddenInput(\Yii::$app->request->csrfParam, \Yii::$app->request->csrfToken);
    }

    /**
     * Button with multiple submit options.
     * @param ActiveForm $form
     * @param $model
     * @param $attribute
     * @param $items
     * @param string $content
     * @param array $options
     * @return string
     */
    public static function submitDropdown(ActiveForm $form, $model, $attribute, $items, $content = "Save", $options = [])
    {
        if (count($items) == 1) {
            return $form->field($model, $attribute, ['options'=>['class' => 'pull-left']])
                ->label(false)->hiddenInput(['value' => key($items)])
                . Html::submitButton(reset($items), $options);
        }
        $lis = '';
        foreach ($items as $key => $name) {
            $lis .= Html::tag('li', "<a href='#'>$name</a>", [
                'onclick' => "$(this).closest('div').find('input').val('{$key}');
                    $(this).parents('form').submit();",
                'class' => $model->$attribute == $key ? 'active' : ''
            ]);
        }

        return Html::beginTag('div', ['class' => 'btn-group'])
                . $form->field($model, $attribute, ['options'=>['class' => 'pull-left']])->label(false)->hiddenInput()
                . Html::button($content . '<span class="caret"></span>',
                    ['class' => 'btn dropdown-toggle btn-primary', "data-toggle"=>"dropdown"])
                . Html::beginTag('ul', ['class'=>'dropdown-menu'])
                    . $lis
                . Html::endTag('ul')
            . Html::endTag('div');
    }

    /**
     * Button with multiple submit options.
     * @param $links
     * @param string $title,
     * @param array $options
     * @return string
     */
    public static function linkDropdown($title, $links, $options = [])
    {
        $lis = '';
        foreach ($links as $name => $url) {
            $lis .= Html::tag('li', Html::a($name, $url));
        }

        return Html::beginTag('div', ['class' => 'btn-group'])
        . Html::button($title . '<span class="caret"></span>',
            array_merge(['class' => 'btn dropdown-toggle btn-primary', "data-toggle"=>"dropdown"], $options))
        . Html::beginTag('ul', ['class'=>'dropdown-menu'])
        . $lis
        . Html::endTag('ul')
        . Html::endTag('div');
    }


    /**
     * Yii1 function
     * Displays the captured PHP error.
     * This method displays the error in HTML when there is
     * no active error handler.
     * @param integer $code error code
     * @param string $message error message
     * @param string $file error file
     * @param string $line error line
     */
    public static function displayError($code,$message,$file,$line)
    {
        if(YII_DEBUG) {
            echo "<h1>PHP Error [$code]</h1>\n";
            echo "<p>$message ($file:$line)</p>\n";
            echo '<pre>';

            $trace=debug_backtrace();
            // skip the first 3 stacks as they do not tell the error position
            if(count($trace)>3)
                $trace=array_slice($trace,3);
            foreach($trace as $i=>$t) {
                if(!isset($t['file']))
                    $t['file']='unknown';
                if(!isset($t['line']))
                    $t['line']=0;
                if(!isset($t['function']))
                    $t['function']='unknown';
                echo "#$i {$t['file']}({$t['line']}): ";
                if(isset($t['object']) && is_object($t['object']))
                    echo get_class($t['object']).'->';
                echo "{$t['function']}()\n";
            }

            echo '</pre>';
        } else {
            echo "<h1>PHP Error [$code]</h1>\n";
            echo "<p>$message</p>\n";
        }
    }

    /**
     * Renders bootstrap3 button group
     * @param $button
     * @param $links
     * @return string
     */
    public static function buttonGroup($button, $links)
    {
        foreach ($links as $key => $link) {
            $links[$key] = Html::beginTag('li')
                    . Html::a($link[0], $link[1], $link[2])
                . Html::endTag('li');
        }

        return Html::beginTag('div', ['class' => 'btn-group'])
                . Html::button($button[0], array_merge([
                    'type'=>"button",
                    'class'=>"btn btn-default dropdown-toggle",
                    'data-toggle'=>"dropdown",
                    'aria-haspopup'=>"true",
                    'aria-expanded'=>"false"
                ], $button[1]))
                . Html::beginTag('ul', ['class' => 'dropdown-menu'])
                    . implode('', $links)
                . Html::endTag('ul')
            . Html::endTag('div');
    }

    /**
     * Returns array as key => value ul->li lists
     * @param $array
     * @return string
     */
    public static function arrayPrettyPrint($array, $raw = false){
        if (!$array) {
            return '';
        }
        $parentOptions = ['tag' => 'dl', 'class' => 'dl-horizontal'];
        $labelOptions = ['tag' => 'dt'];
        $valueOptions = ['tag' => 'dd'];
        $content = [];
        foreach ($array as $key => $value) {
            $value = is_array($value) ? "&nbsp" . static::arrayPrettyPrint($value) : $value;
            $content[] = Html::tag($labelOptions['tag'], $key, $labelOptions);
            $content[] = Html::tag($valueOptions['tag'], $value, $valueOptions);
        }
        return Html::tag($parentOptions['tag'], implode('', $content), $parentOptions);
    }
}