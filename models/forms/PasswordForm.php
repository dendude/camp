<?php
namespace app\models\forms;

use app\models\Users;
use yii\base\Model;

/**
 * Class PasswordForm
 * @package app\models\forms
 */
class PasswordForm extends Model
{
    public $pass_old;
    public $pass_new;
    public $pass_new2;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['pass_old', 'pass_new', 'pass_new2'], 'required'],
            [['pass_old', 'pass_new', 'pass_new2'], 'default', 'value' => ''],
            [['pass_old', 'pass_new', 'pass_new2'], 'string', 'min' => 6, 'message' => 'Введите не менее {min} символов'],
            ['pass_new2', 'compare', 'compareAttribute' => 'pass_new', 'message' => 'Новые пароли должны совпадать'],
            [['pass_old'], 'validatePassword'],
        ];
    }

    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = Users::getProfile();

            if ($user && $user->validatePassword($this->pass_old)) {
                return true;
            } else {
                $this->addError($attribute, 'Неверный текущий пароль');
            }
        }
    }

    public function change()
    {
        if ($this->validate()) {
            $user = Users::getProfile();
            return $user->updateAttributes([
                'pass' => \Yii::$app->security->generatePasswordHash($this->pass_new)
            ]);
        }
        
        return false;
    }
    
    public function attributeLabels()
    {
        return [
            'pass_old' => 'Текущий пароль',
            'pass_new' => 'Новый пароль',
            'pass_new2' => 'Повторите новый пароль',
        ];
    }
}
