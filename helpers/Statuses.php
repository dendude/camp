<?php
/**
 * Created by PhpStorm.
 * User: dendude
 * Date: 15.03.15
 * Time: 20:45
 */

namespace app\helpers;

use yii\helpers\Html;

class Statuses {

    const STATUS_DISABLED = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_USED = 2;

    const STATUS_NEW = 0;
    const STATUS_SHOWED = 1;
    const STATUS_PREPAY = 2;
    const STATUS_PAYED = 3;
    const STATUS_PROCESS = 4;
    const STATUS_CLOSED = 5;

    const STATUS_CANCELED = 9;
    const STATUS_REMOVED = 10;

    const TYPE_FEEDBACK = 'feedback';
    const TYPE_REVIEW = 'review';
    const TYPE_REVIEW_OFFICE = 'review_office';
    const TYPE_EMAIL_MASS = 'email_mass';
    const TYPE_YESNO = 'yesno';
    const TYPE_ACTREM = 'actrem';
    const TYPE_ACTIVE = 'active';
    const TYPE_ORDER = 'order';
    const TYPE_CAMP = 'camp';

    public static function statuses($type = null) {

        switch ($type) {
            case self::TYPE_ORDER:
                $statuses = array(
                    self::STATUS_NEW => 'Ожидает обработки',
                    self::STATUS_SHOWED => 'Обрабатывается',
                    self::STATUS_PREPAY => 'Внесена предоплата',
                    self::STATUS_PAYED => 'Полностью оплачено',
                    self::STATUS_PROCESS => 'Выполняется',
                    self::STATUS_CLOSED => 'Выполнен',
                    self::STATUS_CANCELED => 'Отменен',
                );
                break;
    
            case self::TYPE_EMAIL_MASS:
                $statuses = array(
                    self::STATUS_DISABLED => 'Неактивен',
                    self::STATUS_PROCESS => 'В очереди',
                    self::STATUS_USED => 'Отправляется',
                    self::STATUS_ACTIVE => 'Отправлен',
                    self::STATUS_REMOVED => 'Удален',
                );
                break;

            case self::TYPE_REVIEW:
                $statuses = array(
                    self::STATUS_DISABLED => 'В обработке',
                    self::STATUS_USED => 'Обрабатывается',
                    self::STATUS_ACTIVE => 'Опубликован',
                    self::STATUS_REMOVED => 'Удален',
                );
                break;
    
            case self::TYPE_REVIEW_OFFICE:
                $statuses = self::statuses(self::TYPE_REVIEW);
                unset($statuses[self::STATUS_REMOVED]);
                break;

            case self::TYPE_FEEDBACK:
                $statuses = array(
                    self::STATUS_DISABLED => 'Новый',
                    self::STATUS_SHOWED => 'Просмотрен',
                    self::STATUS_ACTIVE => 'Отвечен',
                );
                break;

            case self::TYPE_YESNO:
                $statuses = array(
                    self::STATUS_ACTIVE => 'Да',
                    self::STATUS_DISABLED => 'Нет',
                );
                break;

            case self::TYPE_ACTIVE:
                $statuses = array(
                    self::STATUS_ACTIVE => 'Активен',
                    self::STATUS_DISABLED => 'Неактивен',
                );
                break;
    
            case self::TYPE_CAMP:
                $statuses = array(
                    self::STATUS_DISABLED => 'Неактивен',
                    self::STATUS_ACTIVE => 'Активен',
                );
                break;

            default:
                $statuses = array(
                    self::STATUS_ACTIVE => 'Активен',
                    self::STATUS_DISABLED => 'Скрыт',
                );
        }

        return $statuses;
    }

    public static function labels() {
        return array(
            self::STATUS_USED => 'primary',
            self::STATUS_SHOWED => 'primary',
            self::STATUS_ACTIVE => 'success',
            self::STATUS_CLOSED => 'success',
            self::STATUS_PREPAY => 'warning',
            self::STATUS_PAYED => 'warning',
            self::STATUS_PROCESS => 'warning',
            self::STATUS_DISABLED => 'default',
            self::STATUS_NEW => 'default',
            self::STATUS_CANCELED => 'danger',
            self::STATUS_REMOVED => 'danger',
        );
    }

    public static function getName($status_id, $type = null) {
        if (is_null($status_id)) $status_id = self::STATUS_DISABLED;
        
        $list = self::statuses($type);
        return isset($list[$status_id]) ? $list[$status_id] : '';
    }

    public static function getLabel($status_id) {
        if (is_null($status_id)) $status_id = self::STATUS_DISABLED;
        
        $list = self::labels();
        return isset($list[$status_id]) ? $list[$status_id] : '';
    }

    public static function getFull($status_id, $type = null) {
        return Html::tag('span', self::getName($status_id, $type), ['class' => 'label label-' . self::getLabel($status_id)]);
    }
} 