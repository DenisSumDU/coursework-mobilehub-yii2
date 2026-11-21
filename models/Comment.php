<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $post_id
 * @property int|null $user_id
 * @property int|null $parent_id
 * @property string $author_name
 * @property string $content
 * @property string $created_at
 *
 * @property Post $post
 * @property User|null $user
 * @property Comment|null $parent
 * @property Comment[] $replies
 */
class Comment extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%comments}}';
    }

    public function rules()
    {
        return [
            [['post_id', 'author_name', 'content'], 'required', 'message' => 'Поле обовʼязкове для заповнення.'],
            [['post_id', 'user_id', 'parent_id'], 'integer'],
            ['author_name', 'filter', 'filter' => static fn($v) => preg_replace('/\s+/u', ' ', trim((string)$v))],
            ['author_name', 'string',
                'min' => 2,
                'max' => 50,
                'tooShort' => 'Імʼя має містити не менше 2 символів.',
                'tooLong' => 'Імʼя не може перевищувати 50 символів.',
            ],
            ['content', 'filter', 'filter' => static fn($v) => trim((string)$v)],
            ['content', 'string',
                'min' => 2,
                'max' => 5000,
                'tooShort' => 'Коментар або відповідь не може бути порожнім.',
            ],
            [
                ['post_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Post::class,
                'targetAttribute' => ['post_id' => 'id'],
            ],
            [
                ['parent_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => self::class,
                'targetAttribute' => ['parent_id' => 'id'],
            ],
            [
                ['user_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['user_id' => 'id'],
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'author_name' => 'Ваше імʼя',
            'content' => 'Текст коментаря / відповіді',
            'parent_id' => 'Коментар, на який відповідаєте',
        ];
    }

    public function getPost()
    {
        return $this->hasOne(Post::class, ['id' => 'post_id']);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getParent()
    {
        return $this->hasOne(self::class, ['id' => 'parent_id']);
    }

    public function getReplies()
    {
        return $this->hasMany(self::class, ['parent_id' => 'id'])
            ->orderBy(['created_at' => SORT_ASC, 'id' => SORT_ASC]);
    }
}