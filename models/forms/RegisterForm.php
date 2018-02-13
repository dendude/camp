<?php

namespace app\models\forms;

use app\components\SmtpEmail;
use app\helpers\Statuses;
use app\models\Actions;
use app\models\Clients;
use app\models\Users;
use app\models\UsersGroups;
use Yii;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\Model;
use yii\helpers\Html;
use yii\helpers\Url;

class RegisterForm extends Model
{
    public $first_name;
    public $last_name;
    public $sur_name;
    public $email;
    public $pass;
    public $pass2;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['first_name', 'email', 'pass', 'pass2'], 'required'],
            [['first_name', 'email'], 'trim'],

            [['first_name', 'last_name', 'sur_name', 'email'], 'string', 'max' => 100],

            ['email', 'email'],
            ['email', 'unique', 'targetClass' => '\app\models\Users', 'message' => 'Такой Email уже зарегистрирован на сайте'],

            ['pass', 'string', 'min' => 6, 'max' => 20, 'tooShort' => 'Введите не менее {min} символов'],
            ['pass2', 'compare', 'compareAttribute' => 'pass', 'message' => 'Пароли не совпадают'],
        ];
    }

    public function register()
    {
        $user = new Users();
        $user->attributes = $this->attributes;
        $user->role = Users::ROLE_USER;
        $user->pass_origin = false;
        $user->setNewActCode();

        if ($user->save()) {
            $smtp = new SmtpEmail();
            $url = Url::to(['/auth/activate', 'id' => $user->id, 'code' => $user->act_code], true);

            $sent = $smtp->sendEmailByType(SmtpEmail::TYPE_REG_REQUEST, $user->email, $user->first_name, [
                '{activate_link}' => Html::a($url, $url)
            ]);

            if (!$sent) $user->delete();

            return $sent;
        } else {
            $this->addErrors($user->getFirstErrors());
        }

        return false;
    }

    public function attributeLabels() {
        return [
            'first_name' => 'Ваше имя',
            'last_name' => 'Фамилия',
            'sur_name' => 'Отчество',
            'email' => 'Ваш Email',
            'pass' => 'Пароль для входа',
            'pass2' => 'Повторите пароль',
        ];
    }
}
