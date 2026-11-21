<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\HtmlPurifier;
use yii\web\UploadedFile;

/**
 * @property int $id
 * @property int $category_id
 * @property int $author_id
 * @property string $title
 * @property string $content
 * @property string|null $image_path
 * @property string $published_date
 *
 * @property Category $category
 * @property User $author
 * @property Tag[] $tags
 * @property Comment[] $comments
 * @property Review[] $reviews
 */
class Post extends ActiveRecord
{
    /**
     * @var UploadedFile|null
     */
    public $imageFile;

    /**
     * @var int[]|string[]|null
     */
    public $tagIds = [];

    public static function tableName()
    {
        return '{{%posts}}';
    }

    public function rules()
    {
        return [
            [['category_id', 'title', 'content', 'published_date'], 'required', 'message' => 'Поле обовʼязкове для заповнення.'],

            [['category_id', 'author_id'], 'integer'],
            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => Category::class, 'targetAttribute' => ['category_id' => 'id']],
            [['author_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['author_id' => 'id']],

            // Попереднє очищення заголовка та основного тексту
            ['title', 'filter', 'filter' => function ($value) {
                $clean = trim((string)$value);
                return preg_replace('/\s+/u', ' ', $clean);
            }],
            ['content', 'filter', 'filter' => function ($value) {
                $normalized = str_replace(["\r\n", "\r"], "\n", (string)$value);
                $normalized = preg_replace("/\n{3,}/", "\n\n", $normalized);
                return trim($normalized);
            }],

            // Санітизація основного тексту від потенційних XSS-атак зі збереженням абзаців
            ['content', 'filter', 'filter' => function ($value) {
                $purified = HtmlPurifier::process((string)$value, [
                    'HTML.Allowed' => 'p,br,b,strong,i,em,u,ul,ol,li,h3,h4,blockquote,code,pre',
                    'AutoFormat.AutoParagraph' => false,
                    'AutoFormat.RemoveEmpty' => false,
                ]);
                return trim(str_replace(["\r\n", "\r"], "\n", $purified));
            }],

            // Довжина заголовка з урахуванням Unicode (mb_strlen)
            ['title', 'string', 'min' => 5, 'max' => 150, 'tooShort' => 'Заголовок має містити не менше 5 символів.', 'tooLong' => 'Заголовок не може перевищувати 150 символів.'],
            ['title', 'validateTitleLength'],

            // Перевірка обсягу тексту: не менше 10 слів українською / Unicode
            ['content', 'validateMinWords', 'params' => ['minWords' => 10]],
            // Формат дати публікації
            ['published_date', 'date', 'format' => 'php:Y-m-d', 'message' => 'Введіть коректну дату у форматі РРРР-ММ-ДД.'],

            // Безпечне завантаження зображення
            ['imageFile', 'file',
                'skipOnEmpty' => true,
                'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
                'wrongExtension' => 'Дозволені формати зображень: jpg, jpeg, png, webp.',
                'maxSize' => 5 * 1024 * 1024, // 5 МБ
                'tooBig' => 'Розмір файлу зображення не може перевищувати 5 МБ.',
            ],

            ['image_path', 'string', 'max' => 255],
            ['tagIds', 'safe'],
        ];
    }

    public function validateTitleLength($attribute, $params)
    {
        if ($this->hasErrors($attribute)) {
            return;
        }

        $length = mb_strlen((string)$this->$attribute, 'UTF-8');
        if ($length < 5 || $length > 150) {
            $this->addError($attribute, 'Довжина заголовка має бути від 5 до 150 символів.');
        }
    }

    public function validateMinWords($attribute, $params)
    {
        if ($this->hasErrors($attribute)) {
            return;
        }

        $minWords = (int)($params['minWords'] ?? 10);
        $plainText = strip_tags((string)$this->$attribute);
        preg_match_all('/[\p{L}\p{N}]+(?:[\'\x{2019}-][\p{L}\p{N}]+)*/u', $plainText, $matches);
        $wordCount = count($matches[0]);

        if ($wordCount < $minWords) {
            $this->addError(
                $attribute,
                "Текст публікації занадто короткий (зараз слів: {$wordCount}). Доповніть текст — мінімальний обсяг становить {$minWords} слів."
            );
        }
    }

    /**
     * Генерує унікальне ім'я файлу, зберігає у web/uploads/ та записує шлях у image_path.
     *
     * @return bool
     */
    public function uploadImage()
    {
        if (!$this->imageFile instanceof UploadedFile) {
            return true;
        }

        $uploadDir = Yii::getAlias('@webroot/uploads');
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            $this->addError('imageFile', 'Не вдалося створити директорію для завантаження зображень.');
            return false;
        }

        $extension = strtolower((string)$this->imageFile->extension);
        $uniqueName = uniqid('post_', true) . '_' . Yii::$app->security->generateRandomString(6) . '.' . $extension;
        $fullPath = $uploadDir . DIRECTORY_SEPARATOR . $uniqueName;

        if (!$this->imageFile->saveAs($fullPath, false)) {
            $this->addError('imageFile', 'Не вдалося зберегти файл зображення на сервері.');
            return false;
        }

        $this->image_path = 'uploads/' . $uniqueName;
        return true;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'category_id' => 'Категорія смартфона',
            'author_id' => 'Автор',
            'title' => 'Заголовок публікації',
            'content' => 'Текст публікації',
            'imageFile' => 'Зображення публікації (jpg, jpeg, png, webp)',
            'image_path' => 'Шлях до зображення',
            'published_date' => 'Дата публікації',
            'tagIds' => 'Мітки (теги)',
        ];
    }

    public function getCategory()
    {
        return $this->hasOne(Category::class, ['id' => 'category_id']);
    }

    public function getAuthor()
    {
        return $this->hasOne(User::class, ['id' => 'author_id']);
    }

    public function getTags()
    {
        return $this->hasMany(Tag::class, ['id' => 'tag_id'])
            ->viaTable('{{%post_tags}}', ['post_id' => 'id']);
    }

    public function getComments()
    {
        return $this->hasMany(Comment::class, ['post_id' => 'id']);
    }

public function getReviews()
    {
        return $this->hasMany(Review::class, ['post_id' => 'id'])
            ->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC]);
    }
}