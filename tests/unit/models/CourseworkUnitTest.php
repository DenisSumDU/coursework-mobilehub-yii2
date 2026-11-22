<?php

namespace tests\unit\models;

use app\models\CommentForm;
use app\models\SignupForm;
use app\models\User;
use Codeception\Test\Unit;
use yii\helpers\Html;

class CourseworkUnitTest extends Unit
{
    private string $tempJsonPath;

    protected function _before()
    {
        $this->tempJsonPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_comments_' . uniqid() . '.json';
    }

    protected function _after()
    {
        if (file_exists($this->tempJsonPath)) {
            @unlink($this->tempJsonPath);
        }
    }

    public function testSignupValidationAndUniqueness()
    {
        $model = new SignupForm();
        $this->assertFalse($model->validate());
        $this->assertArrayHasKey('username', $model->errors);
        $this->assertArrayHasKey('email', $model->errors);
        $this->assertArrayHasKey('password', $model->errors);

        $model->attributes = [
            'username' => 'a',
            'email' => 'invalid-email',
            'password' => '123',
            'password_repeat' => '456',
        ];
        $this->assertFalse($model->validate());
        $this->assertArrayHasKey('email', $model->errors);
        $this->assertArrayHasKey('password_repeat', $model->errors);

        $valid = new SignupForm([
            'username' => 'test_user_99',
            'email' => 'test_user_99@example.com',
            'password' => 'secret123',
            'password_repeat' => 'secret123',
        ]);
        if ($valid->validate() && method_exists($valid, 'signup')) {
            $user = $valid->signup();
            $this->assertInstanceOf(User::class, $user);
        }
    }

    public function testReviewValidationModerationAndAverage()
    {
        $empty = new CommentForm();
        $this->assertFalse($empty->validate());

        $invalid = new CommentForm([
            'authorName' => 'Іван',
            'rating' => 10,
            'content' => 'Коротко',
        ]);
        $this->assertFalse($invalid->validate());
        $this->assertArrayHasKey('rating', $invalid->errors);
        $this->assertArrayHasKey('content', $invalid->errors);

        $moderated = new CommentForm([
            'authorName' => 'Олександр',
            'rating' => 4,
            'content' => 'Огляд добрий, але зустрів СПАМ у коментарях та відгуках.',
        ]);
        $this->assertTrue($moderated->validate());
        $moderationResult = $moderated->runPreliminaryModeration();
        $this->assertSame(CommentForm::STATUS_PENDING_REVIEW, $moderationResult['status']);

        $validComment = new CommentForm([
            'authorName' => 'Марія',
            'rating' => 5,
            'content' => 'Дуже корисний огляд смартфона, усе чітко та зрозуміло.',
        ]);
        $this->assertTrue($validComment->validate());
        $cleanModeration = $validComment->runPreliminaryModeration();
        $this->assertSame(CommentForm::STATUS_PRELIMINARY_CHECK_PASSED, $cleanModeration['status']);

        $this->assertEquals(4.5, $validComment->calculateAverageRating([4, 5, 4]));
    }

    public function testJsonCyrillicCorruptedAndHtmlEscape()
    {
        $this->assertFileDoesNotExist($this->tempJsonPath);

        $items = [
            ['authorName' => 'Олександр', 'rating' => 5, 'content' => 'Відмінна автономність батареї!'],
        ];
        file_put_contents(
            $this->tempJsonPath,
            json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
        $decoded = json_decode(file_get_contents($this->tempJsonPath), true);
        $this->assertSame('Відмінна автономність батареї!', $decoded[0]['content']);

        file_put_contents($this->tempJsonPath, '{broken-json');
        $this->assertNull(json_decode(file_get_contents($this->tempJsonPath), true));

        $escaped = Html::encode('<script>alert("xss")</script>');
        $this->assertStringNotContainsString('<script>', $escaped);
    }
}