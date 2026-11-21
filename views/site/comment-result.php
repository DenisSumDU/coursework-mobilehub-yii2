<?php

/** @var yii\web\View $this */
/** @var app\models\Post $post */
/** @var string $authorName */
/** @var int $rating */
/** @var string $stars */
/** @var string $ratingCategory */
/** @var float $averageRating */
/** @var int $ratingsCount */
/** @var string $content */
/** @var array $moderation */
/** @var string $createdAt */

use yii\helpers\Html;

$this->title = 'Результат відгуку — ' . $post->title;
$this->params['breadcrumbs'][] = ['label' => 'Огляди', 'url' => ['site/index']];
$this->params['breadcrumbs'][] = ['label' => $post->title, 'url' => ['post/view', 'id' => $post->id]];
$this->params['breadcrumbs'][] = 'Результат';

$isPassed = ($moderation['status'] ?? '') === 'preliminary_check_passed';
$alertClass = $isPassed ? 'alert-info' : 'alert-warning';
?>

<div class="site-comment-result">
    <div class="card shadow-sm border-0 rounded-4 p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <div class="text-muted small">Допис: <strong><?= Html::encode($post->title) ?></strong></div>
                <h1 class="h3 fw-bold mt-1 mb-0">Результат опрацювання відгуку</h1>
            </div>
            <div class="text-muted small">
                Дата й час створення: <strong><?= Html::encode($createdAt) ?></strong>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="p-3 rounded-3 bg-light border h-100">
                    <div class="small text-muted">Автор відгуку</div>
                    <div class="fw-bold fs-6 mt-1"><?= Html::encode($authorName) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 rounded-3 bg-light border h-100">
                    <div class="small text-muted">Оцінка та категорія</div>
                    <div class="fw-bold fs-6 text-warning mt-1"><?= Html::encode($stars) ?> (<?= (int)$rating ?>/5)</div>
                    <div class="small mt-1">Категорія оцінки: <strong><?= Html::encode($ratingCategory) ?></strong></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 rounded-3 bg-light border h-100">
                    <div class="small text-muted">Середня оцінка допису</div>
                    <div class="fw-bold fs-5 text-primary mt-1"><?= Html::encode((string)$averageRating) ?> / 5</div>
                    <div class="small text-muted">Всього оцінок: <?= (int)$ratingsCount ?></div>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <div class="small text-muted fw-semibold mb-1">Текст відгуку</div>
            <div class="p-3 rounded-3 bg-light border">
                <?= nl2br(Html::encode($content)) ?>
            </div>
        </div>

        <div class="alert <?= $alertClass ?> rounded-3 mb-4">
            <div class="fw-bold mb-1">Попередня модерація: <?= Html::encode($moderation['statusLabel'] ?? '') ?></div>
            <div class="mb-0"><?= Html::encode($moderation['message'] ?? '') ?></div>
            <?php if (!empty($moderation['matchedPhrases'])): ?>
                <div class="small mt-2">
                    Виявлені збіги: <strong><?= Html::encode(implode(', ', $moderation['matchedPhrases'])) ?></strong>
                </div>
            <?php endif; ?>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <?= Html::a('Залишити ще один відгук', ['site/comment', 'id' => $post->id], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('До перегляду допису', ['post/view', 'id' => $post->id], ['class' => 'btn btn-outline-secondary']) ?>
            <?= Html::a('На головну', ['site/index'], ['class' => 'btn btn-outline-dark']) ?>
        </div>
    </div>
</div>