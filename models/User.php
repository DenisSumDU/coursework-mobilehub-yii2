<?php

namespace app\models;

use Yii;
use yii\base\NotSupportedException;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

class User extends ActiveRecord implements IdentityInterface
{
    public const ROLE_READER = 'reader';
    public const ROLE_AUTHOR = 'author';

    public static function tableName()
    {
        return '{{%users}}';
    }

    public function rules()
    {
        return [
            [['username', 'email', 'password_hash', 'auth_key'], 'required'],
            [['username'], 'string', 'min' => 3, 'max' => 50],
            [['email'], 'string', 'max' => 100],
            [['email'], 'email'],
            [['password_hash'], 'string', 'max' => 255],
            [['auth_key'], 'string', 'max' => 32],
            [['role'], 'in', 'range' => [self::ROLE_READER, self::ROLE_AUTHOR]],
            [['username'], 'unique'],
            [['email'], 'unique'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Ім*я',
            'email' => 'Електронна пошта',
            'role' => 'Роль',
            'created_at' => 'Дата реєстрації',
        ];
    }

    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id]);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username]);
    }

    public function getId()
    {
        return $this->getPrimaryKey();
    }

    public function getAuthKey()
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    public function getPosts()
    {
        return $this->hasMany(Post::class, ['author_id' => 'id']);
    }
}