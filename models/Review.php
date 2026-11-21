<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $post_id
 * @property string $author_name
 * @property int $rating
 * @property string $content
 * @property string $moderation_status
 * @property string $created_at
 *
 * @property Post $post
 */
class Review extends ActiveRecord
{
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_PRELIMINARY_CHECK_PASSED = 'preliminary_check_passed';

    public static function tableName()
    {
        return '{{%reviews}}';
    }

    public function rules()
    {
        return [
            [['post_id', 'author_name', 'rating', 'content', 'moderation_status'], 'required'],
            [['post_id', 'rating'], 'integer'],
            ['rating', 'integer', 'min' => 1, 'max' => 5],
            ['author_name', 'string', 'min' => 2, 'max' => 50],
            ['content', 'string', 'min' => 10, 'max' => 1000],
            ['moderation_status', 'in', 'range' => [
                self::STATUS_PENDING_REVIEW,
                self::STATUS_PRELIMINARY_CHECK_PASSED,
            ]],
            ['created_at', 'safe'],
            [
                ['post_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Post::class,
                'targetAttribute' => ['post_id' => 'id'],
            ],
        ];
    }

    public function getPost()
    {
        return $this->hasOne(Post::class, ['id' => 'post_id']);
    }
}