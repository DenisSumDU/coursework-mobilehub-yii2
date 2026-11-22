<?php

namespace tests\functional;

use FunctionalTester;

class LoginFormCest
{
    public function _before(FunctionalTester $I)
    {
        $I->amOnRoute('site/login');
    }

    public function openLoginPage(FunctionalTester $I)
    {
        $I->see('Вхід в акаунт', 'h1');
    }

    public function internalLoginById(FunctionalTester $I)
    {
        $I->amLoggedInAs(100);
        $I->amOnPage('/');
        $I->seeResponseCodeIs(200);
    }

    public function internalLoginByInstance(FunctionalTester $I)
    {
        $user = \app\models\User::findIdentity(100);
        $I->amLoggedInAs($user);
        $I->amOnPage('/');
        $I->seeResponseCodeIs(200);
    }

    public function loginWithEmptyCredentials(FunctionalTester $I)
    {
        $I->submitForm('#login-form', []);
        $I->expectTo('see validations errors');
        $I->see('Поле обовʼязкове для заповнення.');
    }

    public function loginWithWrongCredentials(FunctionalTester $I)
    {
        $I->submitForm('#login-form', [
            'LoginForm[username]' => 'admin',
            'LoginForm[password]' => 'wrong',
        ]);
        $I->expectTo('see validations errors');
        $I->see('Невірний логін або пароль.');
    }

    public function loginSuccessfully(FunctionalTester $I)
    {
        $I->submitForm('#login-form', [
            'LoginForm[username]' => 'admin',
            'LoginForm[password]' => 'admin',
        ]);
        $I->seeResponseCodeIs(200);
        $I->dontSee('Невірний логін або пароль.');
    }
}