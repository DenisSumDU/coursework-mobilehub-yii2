<?php

namespace app\models;

use yii\base\Model;

class CommentForm extends Model
{
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_PRELIMINARY_CHECK_PASSED = 'preliminary_check_passed';

    public $authorName;
    public $rating;
    public $content;

    /**
     * Список тестових слів і словосполучень небажаного вмісту (перевірка без урахування регістру).
     *
     * @var string[]
     */
    public array $forbiddenPhrases = [
        'спам',
        'реклама',
        'казино',
        'тестовий відгук',
        'фейк',
        'шахрайство',
        'купити дешево',
    ];

    public function rules()
    {
        return [
            [['authorName', 'rating', 'content'], 'required', 'message' => 'Поле обовʼязкове для заповнення.'],

            ['authorName', 'filter', 'filter' => static fn($v) => preg_replace('/\s+/u', ' ', trim((string)$v))],
            ['authorName', 'string',
                'min' => 2,
                'max' => 50,
                'tooShort' => 'Імʼя автора має містити не менше 2 символів.',
                'tooLong' => 'Імʼя автора не може перевищувати 50 символів.',
            ],

            ['rating', 'integer',
                'min' => 1,
                'max' => 5,
                'tooSmall' => 'Оцінка має бути цілим числом від 1 до 5.',
                'tooBig' => 'Оцінка має бути цілим числом від 1 до 5.',
                'message' => 'Оцінка має бути цілим числом від 1 до 5.',
            ],

            ['content', 'filter', 'filter' => static fn($v) => trim(str_replace(["\r\n", "\r"], "\n", (string)$v))],
            ['content', 'string',
                'min' => 10,
                'max' => 1000,
                'tooShort' => 'Текст відгуку має містити не менше 10 символів.',
                'tooLong' => 'Текст відгуку не може перевищувати 1000 символів.',
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'authorName' => 'Імʼя автора відгуку',
            'rating' => 'Оцінка допису (1–5 зірок)',
            'content' => 'Текст відгуку',
        ];
    }

    public function getNumericRating(): int
    {
        return (int)$this->rating;
    }

    public function getRatingCategory(): string
    {
        $score = $this->getNumericRating();

        if ($score <= 2) {
            return 'низька';
        }

        if ($score === 3) {
            return 'середня';
        }

        return 'висока';
    }

    public function getStarsString(): string
    {
        $score = max(1, min(5, $this->getNumericRating()));
        return str_repeat('★', $score) . str_repeat('☆', 5 - $score);
    }

    /**
     * Попередня автоматизована модерація без урахування регістру.
     *
     * @return array{status: string, statusLabel: string, matchedPhrases: string[], message: string}
     */
    public function runPreliminaryModeration(): array
    {
        $text = (string)$this->content;
        $matched = [];

        foreach ($this->forbiddenPhrases as $phrase) {
            $cleanPhrase = trim($phrase);
            if ($cleanPhrase === '') {
                continue;
            }
            if (mb_stripos($text, $cleanPhrase, 0, 'UTF-8') !== false) {
                $matched[] = $cleanPhrase;
            }
        }

        if (!empty($matched)) {
            return [
                'status' => self::STATUS_PENDING_REVIEW,
                'statusLabel' => 'Потребує додаткової перевірки (pending_review)',
                'matchedPhrases' => $matched,
                'message' => 'У тексті відгуку виявлено слова або словосполучення зі списку небажаного вмісту. Відгук отримав статус pending_review і потребує додаткової перевірки.',
            ];
        }

        return [
            'status' => self::STATUS_PRELIMINARY_CHECK_PASSED,
            'statusLabel' => 'Пройшов попередню автоматизовану перевірку (preliminary_check_passed)',
            'matchedPhrases' => [],
            'message' => 'Відгук отримав статус preliminary_check_passed. Зауваження: перевірка за списком слів є спрощеним механізмом і не означає остаточного схвалення до публікації.',
        ];
    }

    /**
     * Обчислює середню оцінку допису з урахуванням попередніх оцінок із БД та нового відгуку.
     *
     * @param int[] $previousRatings
     */
    public function calculateAverageRating(array $previousRatings): float
    {
        $ratings = array_map('intval', $previousRatings);
        $ratings[] = $this->getNumericRating();

        return round(array_sum($ratings) / count($ratings), 2);
    }
}