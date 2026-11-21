<?php

namespace app\models;

use Yii;
use yii\base\Model;

class SignupForm extends Model
{
    public $username;
    public $email;
    public $password;
    public $password_repeat;

    public function rules()
    {
        return [
            [['username', 'email', 'password', 'password_repeat'], 'required', 'message' => 'Поле обовʼязкове для заповнення.'],
            [['username', 'email'], 'trim'],

            ['username', 'string', 'min' => 3, 'max' => 50, 'tooShort' => 'Логін має містити не менше 3 символів.', 'tooLong' => 'Логін не може перевищувати 50 символів.'],
            ['username', 'match', 'pattern' => '/^[a-zA-Z0-9_-]+$/', 'message' => 'Логін може містити лише латинські літери, цифри, дефіс та підкреслення.'],
            ['username', 'unique', 'targetClass' => User::class, 'message' => 'Користувач із таким логіном уже зареєстрований.'],

            ['email', 'email', 'message' => 'Введіть коректну адресу електронної пошти.'],
            ['email', 'string', 'max' => 100],
            ['email', 'unique', 'targetClass' => User::class, 'message' => 'Ця адреса електронної пошти вже використовується.'],

            ['password', 'string', 'min' => 6, 'max' => 128, 'tooShort' => 'Пароль має містити не менше 6 символів.'],
            ['password_repeat', 'compare', 'compareAttribute' => 'password', 'message' => 'Пароль і підтвердження пароля не збігаються.'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'username' => 'Ім*я',
            'email' => 'Електронна пошта',
            'password' => 'Пароль',
            'password_repeat' => 'Підтвердження пароля',
        ];
    }

    public function signup()
    {
        if (!$this->validate()) {
            return null;
        }

        $user = new User();
        $user->username = $this->username;
        $user->email = $this->email;
        $user->role = User::ROLE_READER;
        $user->setPassword($this->password);
        $user->generateAuthKey();

        return $user->save() ? $user : null;
    }
}