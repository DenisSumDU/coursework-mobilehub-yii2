<?php

namespace app\models;

use yii\db\ActiveRecord;

class Tag extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%tags}}';
    }

    public function rules()
    {
        return [
            [['name', 'slug'], 'required'],
            [['name'], 'string', 'max' => 50],
            [['slug'], 'string', 'max' => 60],
            [['name'], 'unique'],
            [['slug'], 'unique'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Мітка',
            'slug' => 'URL-слаг',
        ];
    }

    public function getPosts()
    {
        return $this->hasMany(Post::class, ['id' => 'post_id'])
            ->viaTable('{{%post_tags}}', ['tag_id' => 'id']);
    }
}