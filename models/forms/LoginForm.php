<?php
namespace app\models\forms;

use app\helpers\Statuses;
use app\models\Users;
use yii\base\Model;

class LoginForm extends Model
{
    public $email;
    public $password;

    private $_user = null;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['email', 'password'], 'required'],
            
            ['email', 'email'],
            [['email', 'password'], 'string', 'max' => 100],
            
            ['password', 'validatePassword'],
        ];
    }

    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();

            if ($user && $user->validatePassword($this->password)) {
                if ($user->status == Statuses::STATUS_ACTIVE) {
                    // ok
                } elseif ($user->status == Statuses::STATUS_DISABLED) {
                    $this->addError($attribute, 'Пользователь не активирован! Пройдите по ссылке в письме.');
                } else {
                    $this->addError($attribute, 'Пользователь не найден.');
                }
            } else {
                $this->addError($attribute, 'Пользователь не найден, проверьте логин и пароль.');
            }
        }
    }

    public function login()
    {
        if ($this->validate()) {
            return \Yii::$app->user->login($this->getUser(), 3600*24*30);
        }
        return false;
    }
    
    /**
     * @return Users|null
     */
    public function getUser()
    {
        if ($this->_user === null) {
            $this->_user = Users::find()->where(['email' => $this->email])->one();
        }

        return $this->_user;
    }
    
    public function attributeLabels()
    {
        return [
            'email' => 'Email',
            'password' => 'Пароль',
        ];
    }
}
