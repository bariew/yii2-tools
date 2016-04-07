<?php
/**
 * Created by PhpStorm.
 * User: pt
 * Date: 07.04.16
 * Time: 21:38
 */

namespace bariew\yii2Tools\helpers;


class MathHelper
{
    public static function percentage($part, $total)
    {
        if (!$total) {
            return 0;
        }
        return $part / $total * 100;
    }
}