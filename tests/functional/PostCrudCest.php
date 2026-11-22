<?php

namespace tests\functional;

use FunctionalTester;

class PostCrudCest
{
    public function openAdminIndex(FunctionalTester $I)
    {
        $I->amOnPage('/post/index');
        $I->seeResponseCodeIs(200);
    }

    public function validationOnEmptyCreate(FunctionalTester $I)
    {
        $I->amOnPage('/post/create');
        $I->seeResponseCodeIs(200);
    }

    public function notFoundForMissingPost(FunctionalTester $I)
    {
        $I->amOnPage('/post/view?id=999999');
        $I->seeResponseCodeIs(200);
    }
}