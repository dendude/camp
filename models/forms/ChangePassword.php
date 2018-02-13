<?php

namespace app\models\forms;

use app\helpers\Statuses;
use app\models\Users;
use Yii;
use yii\base\Model;

class ChangePassword extends Model
{
    public $pass_old;
    public $pass_new;
    public $pass_new2;

    /** @var $_user Users */
    private $_user = false;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['pass_old', 'pass_new', 'pass_new2'], 'required'],
            [['pass_old', 'pass_new', 'pass_new2'], 'string', 'min' => 8, 'tooShort' => 'Введіть не менш як {min} символів'],

            [['pass_old'], 'checkCurrentPassword'],
            [['pass_new2'], 'compare', 'compareAttribute' => 'pass_new', 'message' => 'Нові паролі не співпадають'],
        ];
    }

    public function checkCurrentPassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $this->_user = Users::$profile;

            if (!$this->_user->validatePassword($this->{$attribute})) {
                $this->addError($attribute, 'Помилковий поточний пароль');
            }
        }
    }

    /**
     * Logs in a user using the provided username and password.
     * @return boolean whether the user is logged in successfully
     */
    public function changePassword()
    {
        if ($this->validate()) {

            $this->_user->updateAttributes([
                'passw' => Yii::$app->security->generatePasswordHash($this->pass_new)
            ]);

            return true;
        }
        return false;
    }


    public function attributeLabels() {
        return [
            'pass_old' => 'Поточний пароль',
            'pass_new' => 'Новий пароль',
            'pass_new2' => 'Повтор нового паролю',
        ];
    }
}
