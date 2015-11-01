<?php
/**
 * Created by PhpStorm.
 * User: pt
 * Date: 01.11.15
 * Time: 13:32
 */

namespace bariew\yii2Tools\helpers;
use yii\base\Event;
use yii\db\ActiveRecord;

class EventHelper
{
    public static function isAttributeChanged(Event $event, $attribute)
    {
        /** @var ActiveRecord $sender */
        $sender = $event->sender;
        if (!isset($event->changedAttributes)) {
            return $sender->isAttributeChanged($attribute);
        }
        return @$event->changedAttributes[$attribute] != $sender->getAttribute($attribute);
    }
}