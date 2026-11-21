<?php

namespace app\models;

use yii\db\ActiveRecord;

class Category extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%categories}}';
    }

    public function rules()
    {
        return [
            [['name', 'slug'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['slug'], 'string', 'max' => 120],
            [['description'], 'string'],
            [['slug'], 'unique'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Назва категорії',
            'slug' => 'URL-слаг',
            'description' => 'Опис категорії',
        ];
    }

    public function getPosts()
    {
        return $this->hasMany(Post::class, ['category_id' => 'id']);
    }
}