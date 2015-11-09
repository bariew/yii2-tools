<?php
/**
 * Item class file.
 * @copyright (c) 2015, Pavel Bariev
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace bariew\yii2Tools\helpers;
use yii\base\Event;
use yii\db\ActiveRecord;

/**
 * See README
 *
 * @author Pavel Bariev <bariew@yandex.ru>
 *
 */
class EventHelper
{
    /**
     * Checks whether event sender attribute is changed.
     * @param Event $event
     * @param $attribute
     * @return bool
     */
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