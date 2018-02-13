<?php

namespace app\models\forms;

use app\components\SmtpEmail;
use app\helpers\Normalize;
use app\helpers\Statuses;
use app\models\Actions;
use app\models\Clients;
use app\models\Users;
use Yii;
use yii\base\Model;
use yii\bootstrap\Html;
use yii\helpers\Url;

class RestoreForm extends Model
{
    public $name;
    public $email;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['email','name'], 'required'],
            [['email','name'], 'trim'],
            [['email'], 'email'],
            [['email'], 'exist', 'targetAttribute' => 'email', 'targetClass' => Users::className(), 'message' => 'Такой Email не зарегистрирован на сайте'],
        ];
    }

    public function restore() {
        
        /** @var $user Users */
        $user = Users::find()->where(['email' => $this->email])->one();
        $user->setNewActCode();

        $smtp = new SmtpEmail();
        $url = Url::to(['reset', 'id' => $user->id, 'code' => $user->act_code], true);

        return $smtp->sendEmailByType(SmtpEmail::TYPE_RESTORE_REQUEST, $this->email, $this->name, [
            '{confirm_link}' => Html::a($url, $url)
        ]);
    }

    public function attributeLabels() {
        return [
            'name' => 'Ваше имя',
            'email' => 'Ваш Email',
        ];
    }
}
