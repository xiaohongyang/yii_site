<?php
/**
 * Created by PhpStorm.
 * User: xiaohongyang
 * Date: 2016/12/9
 * Time: 8:21
 */

namespace app\modules\admin\models;


use yii\behaviors\TimestampBehavior;
use yii\captcha\CaptchaValidator;
use yii\web\IdentityInterface;

class AdminModel  extends BaseModel implements IdentityInterface
{

    //    `username` varchar(50) NOT NULL COMMENT '用户名',
//    `password` varchar(100) NOT NULL COMMENT '用户密码',
//    `mobile` varchar(20) NOT NULL COMMENT '用户手机号',
//    `authKey` varchar(100) NOT NULL DEFAULT '' COMMENT 'authKey',
//    `accessToken` varchar(100) NOT NULL DEFAULT '' COMMENT 'accessToken',
//    `created_at` int(10) DEFAULT '0' COMMENT '添加时间',
//    `updated_at` int(10) DEFAULT '0' COMMENT '更新时间',

    public $new_password;
    public $captcha;

    public static function tableName()
    {
        return parent::getDbPrefix(). 'admin'; // TODO: Change the autogenerated stub
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className()
            ]
        ];
    }


    public function rules()
    {
        return [
            [
                [
                    'username',
                    'password',
                    'mobile'
                ],
                'required',
                'on' => self::SCENARIO_CREATE
            ],
            [
                [
                    'username',
                    'password',
                    //'captcha',
                ],
                'required',
                'on' => self::SCENARIO_LOGIN
            ],
          /*  [
                'captcha', 'captcha', 'on' => self::SCENARIO_LOGIN
            ]*/
        ];
    }

    public function scenarios()
    {
        return [
            self::SCENARIO_CREATE => [
                'username',
                'password',
                'mobile',
                'authKey',
                'accessToken',
            ], self::SCENARIO_LOGIN => [
                'password',
                'username',
                'captcha',
            ], self::SCENARIO_UPDATE => [
                'username',
                'password',
                'mobile',
                'authKey',
                'accessToken',
            ]
        ];
    }

    /**
     * 添加用户
     * @param $data
     * @return bool
     */
    public function create($data){

        $rs = false;
        if( !key_exists(self::formName(), $data)) {
            $data = [
                self::formName() => $data
            ];
        }

        $this->scenario = self::SCENARIO_CREATE;
        if($this->load($data) && $this->validate()) {
            $rs = $this->save();
        }
        return $rs;
    }

    public function login($data){

        $rs = false;
        if( !key_exists(self::formName(), $data)) {
            $data = [
                self::formName() => $data
            ];
        }
        $this->scenario = self::SCENARIO_LOGIN;
        if ($this->load($data) && $this->validate()) {
            $model = self::findOne(['username' => $this->username]);
            if($model && $model->validatePassword($this->password)) {
                $rs = true;
            } else {
                $this->addError('password', '用户名或密码错误');
            }
        }
        return $rs;
    }

    public function edit($data){

        $rs = false;
        if (!key_exists(self::formName(), $data)) {
            $data = [
                self::formName() => $data
            ];
        }
        $this->scenario = self::SCENARIO_UPDATE;
        if ($this->load($data) && $this->validate()) {
            $rs = $this->save();
        }
        return $rs;
    }

    public function beforeSave($insert)
    {
        if ($insert) {
            //添加时,保存密码
            $this->password = $this->_generatePassword();
        } else if ($this->password && $this->password != $this->getOldAttribute('password')){
            //如果有输入密码,更新密码
            $this->password = $this->_generatePassword();
        }
        return parent::beforeSave($insert); // TODO: Change the autogenerated stub
    }

    public function attributeLabels()
    {
        return [
            'username' => '登录名',
            'mobile' => '手机号',
            'password' => '密码',
            'captcha' => '验证码',
        ];
    }

    public function  captcha()
    {

        $captcha = $this->captcha;
        $captchValidator = new CaptchaValidator();
        $captchValidator->captchaAction = '/admin/public/captcha';

        if (!$captchValidator->validate($captcha)) {
            $this->addError('captcha', '验证码错误!');
        }
    }

    #region primary method
    private function _generateAuthKey(){
        return \Yii::$app->security->generateRandomString();
    }

    private function _generatePassword() {
        $this->authKey = $this->_generateAuthKey();
        return md5($this->password . $this->authKey);
    }

    private function _getDbPassword($password) {
        return md5($password . $this->authKey);
    }

    /**
     * 验证密码
     * @param $password
     * @return bool
     */
    public function validatePassword($password) {


        return $this->_getDbPassword($password) == $this->password;
    }
    #endregion

    /**
     * Finds an identity by the given ID.
     * @param string|integer $id the ID to be looked for
     * @return IdentityInterface the identity object that matches the given ID.
     * Null should be returned if such an identity cannot be found
     * or the identity is not in an active state (disabled, deleted, etc.)
     */
    public static function findIdentity($id)
    {
        // TODO: Implement findIdentity() method.
        return AdminModel::findOne(['id'=>$id]);
    }

    /**
     * Finds an identity by the given token.
     * @param mixed $token the token to be looked for
     * @param mixed $type the type of the token. The value of this parameter depends on the implementation.
     * For example, [[\yii\filters\auth\HttpBearerAuth]] will set this parameter to be `yii\filters\auth\HttpBearerAuth`.
     * @return IdentityInterface the identity object that matches the given token.
     * Null should be returned if such an identity cannot be found
     * or the identity is not in an active state (disabled, deleted, etc.)
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        // TODO: Implement findIdentityByAccessToken() method.
        return AdminModel::findOne(['accessToken' => $token]);
    }

    /**
     * Returns an ID that can uniquely identify a user identity.
     * @return string|integer an ID that uniquely identifies a user identity.
     */
    public function getId()
    {
        // TODO: Implement getId() method.
        return $this->id;
    }

    /**
     * Returns a key that can be used to check the validity of a given identity ID.
     *
     * The key should be unique for each individual user, and should be persistent
     * so that it can be used to check the validity of the user identity.
     *
     * The space of such keys should be big enough to defeat potential identity attacks.
     *
     * This is required if [[User::enableAutoLogin]] is enabled.
     * @return string a key that is used to check the validity of a given identity ID.
     * @see validateAuthKey()
     */
    public function getAuthKey()
    {
        // TODO: Implement getAuthKey() method.
        return $this->authKey;
    }

    /**
     * Validates the given auth key.
     *
     * This is required if [[User::enableAutoLogin]] is enabled.
     * @param string $authKey the given auth key
     * @return boolean whether the given auth key is valid.
     * @see getAuthKey()
     */
    public function validateAuthKey($authKey)
    {
        // TODO: Implement validateAuthKey() method.
        return $this->getAuthKey() == $authKey;
    }
}