<?php

namespace app\models;

use app\components\ReCaptcha;
use app\helpers\Normalize;
use app\models\queries\ReviewsQuery;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\Json;
use yii\web\Cookie;

/**
 * This is the model class for table "{{%reviews}}".
 *
 * @property integer $id
 * @property integer $manager_id
 * @property integer $partner_id
 * @property integer $base_id
 * @property integer $user_id
 * @property string $user_name
 * @property string $user_email
 * @property string $comment_positive
 * @property string $comment_negative
 * @property string $comment_manager
 * @property string $votes
 * @property integer $stars
 * @property integer $likes
 * @property integer $created
 * @property integer $modified
 * @property integer $status
 * @property integer $ordering
 *
 * @property Camps $camp
 */
class Reviews extends ActiveRecord
{
    public $user_notice = false;
    public $votes_arr = [];
    
    public $captcha;
    
    const SCENARIO_SITE = 'site';
    const MAX_VOTES = 10;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%reviews}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['partner_id', 'base_id', 'user_name', 'user_email', 'created', 'comment_positive'], 'required'],

            [['manager_id', 'base_id', 'user_id', 'likes', 'created', 'modified', 'status', 'ordering'], 'integer'],
            [['manager_id', 'base_id', 'user_id', 'likes', 'created', 'modified', 'status', 'ordering'], 'default', 'value' => 0],

            [['stars'], 'number', 'min' => 0, 'max' => 10],
            [['stars'], 'default', 'value' => 0],

            [['user_name', 'user_email'], 'string', 'max' => 100],
            [['comment_positive', 'comment_negative', 'comment_manager'], 'string', 'min' => 20, 'max' => 1000],

            ['user_notice', 'boolean'],
            
            ['votes', 'string'],
            ['votes_arr', 'each', 'rule' => ['filter', 'filter' => 'intval']],
            ['votes_arr', 'each', 'rule' => ['integer']],
            ['votes_arr', 'checkCountVotes', 'skipOnEmpty' => false],

            ['captcha', 'required', 'message' => 'Необходимо отметить поле "Я не робот"', 'on' => self::SCENARIO_SITE],
            ['captcha', 'checkCaptcha', 'on' => self::SCENARIO_SITE]
        ];
    }
    
    public function checkCaptcha($attribute, $params) {
        if ($this->hasErrors()) return;
        
        $re_captcha = new ReCaptcha($this->{$attribute});
        if (!$re_captcha->validate()) {
            $this->addError($attribute, 'Некорректное значение reCaptcha');
        }
    }
    
    public function checkCountVotes($attribute, $params) {
        if (count($this->{$attribute}) != ReviewsItems::find()->active()->count()) {
            $this->addError($attribute, 'Пожалуйста, оцените лагерь по всем критериям');
        }
    }

    public function getCamp() {
        return $this->hasOne(Camps::className(), ['id' => 'base_id']);
    }

    public function beforeValidate()
    {
        if ($this->isNewRecord) {
            $this->created = time();
        } else {
            $this->modified = time();
        }
        
        if ($this->base_id && $this->camp) $this->partner_id = $this->camp->partner_id;
        if (!Yii::$app->user->isGuest) {
            $this->user_id = Yii::$app->user->id;
            $this->user_email = Yii::$app->user->identity->email;
            $this->user_name = Yii::$app->user->identity->first_name;
        }
        if (Users::isAdmin()) $this->manager_id = Yii::$app->user->id;
        if ($this->votes_arr) $this->votes = Json::encode($this->votes_arr);
    
        if ($this->scenario == self::SCENARIO_SITE) {
            $this->captcha = Yii::$app->request->post(ReCaptcha::FIELD_NAME);
        }

        return parent::beforeValidate();
    }
    
    public function beforeSave($insert)
    {
        if (!$this->hasErrors() && $this->votes_arr) {
            // кол-ва баллов за один вопрос, учитывая базис баллов
            // например если 8 вопросов всего, то за каждые 5 баллов ответа мы получим 10/8 = 1,25 баллов,
            // что позволит набрать 10 баллов за все 8 вопросов
            $votes_by_item = (self::MAX_VOTES / count($this->votes_arr));
            // начинаем считать баллы
            $votes_sum = 0;
            
            foreach ($this->votes_arr AS $id => $val) {
                $votes_sum += ($votes_by_item * ($val / 5));
            }
            $this->stars = round($votes_sum, 1);
        }
        
        return parent::beforeSave($insert);
    }
    
    
    public function afterFind()
    {
        if ($this->votes) $this->votes_arr = Json::decode($this->votes);
        
        parent::afterFind();
    }
    
    
    protected function getLikesCookieName() {
        return md5('review-like-' . $this->id);
    }

    public function hasLike() {
        $cookies = Yii::$app->request->cookies;
        $cookie_name = $this->getLikesCookieName();

        return $cookies->has($cookie_name);
    }

    public function updateLikes() {
        $cookie_exp = time() + 7*24*3600; // куки на 7 дней
        $cookie_name = $this->getLikesCookieName();

        $cookies = Yii::$app->request->cookies;
        // сохраняем клик в куки
        $cookies->add(new Cookie(['name' => $cookie_name, 'value' => 1, 'expire' => $cookie_exp]));
        // наращиваем счетчик
        $this->updateCounters(['likes' => 1]);
    }

    public function afterSave($insert, $changedAttributes)
    {
        $count_reviews = self::find()->byCamp($this->base_id)->active()->count();
        $sum_stars = self::find()->byCamp($this->base_id)->active()->sum('stars');

        // обновляем оценку после модификации отзыва
        $result_stars = $count_reviews ? round($sum_stars / $count_reviews, 1) : 0;
        $this->camp->updateAttributes(['stars' => $result_stars]);

        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return Normalize::withCommonLabels([
            'base_id' => 'Лагерь',
            'user_id' => 'Пользователь',
            'user_name' => 'Имя пользователя',
            'user_email' => 'Email пользователя',
            'comment_positive' => 'Достоинства',
            'comment_negative' => 'Недостатки',
            'comment_manager' => 'Ответ менеджера',
            'votes_arr' => 'Оценка лагеря',
            'votes' => 'Общая оценка',
            'stars' => 'Оценка',
            'likes' => 'Лайков',

            'user_notice' => 'Отправить письмо автору отзыва'
        ]);
    }

    public static function find()
    {
        return new ReviewsQuery(get_called_class());
    }
}
