<?php

/** @var yii\web\View $this */
/** @var app\models\Post $model */
/** @var app\models\Review[] $reviews */
/** @var array $reviewStats */
/** @var app\models\Comment[] $rootComments */
/** @var array $repliesByParent */
/** @var app\models\Comment $newCommentForm */

use app\models\Comment;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Адмін панель публікацій', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$identity = Yii::$app->user->identity;
$canManage = !Yii::$app->user->isGuest && $identity !== null && (
    (isset($identity->role) && in_array($identity->role, ['admin', 'author'], true))
    || (isset($identity->username) && $identity->username === 'admin_author')
);

$reviews = $reviews ?? [];
$reviewStats = $reviewStats ?? [
    'count' => 0,
    'average' => 0.0,
    'stars' => '☆☆☆☆☆',
    'category' => 'немає оцінок',
    'pendingCount' => 0,
];

$rootComments = $rootComments ?? [];
$repliesByParent = $repliesByParent ?? [];
$newCommentForm = $newCommentForm ?? new Comment();
$defaultAuthor = (!Yii::$app->user->isGuest && Yii::$app->user->identity)
    ? (string)Yii::$app->user->identity->username
    : '';

$shareUrl = Url::to(['post/view', 'id' => $model->id], true);
$shareText = $model->title;
$telegramUrl = 'https://t.me/share/url?' . http_build_query([
    'url' => $shareUrl,
    'text' => $shareText,
]);
$facebookUrl = 'https://www.facebook.com/sharer/sharer.php?' . http_build_query([
    'u' => $shareUrl,
]);
$twitterUrl = 'https://twitter.com/intent/tweet?' . http_build_query([
    'url' => $shareUrl,
    'text' => $shareText,
]);
?>

<div class="post-view py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h2 mb-1"><?= Html::encode($model->title) ?></h1>
            <div class="text-muted small">
                Категорія: <strong><?= Html::encode($model->category ? $model->category->name : '—') ?></strong> •
                Дата: <strong><?= Html::encode($model->published_date) ?></strong>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if ($canManage): ?>
                <?= Html::a('Редагувати', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
                <?= Html::a('Видалити', ['delete', 'id' => $model->id], [
                    'class' => 'btn btn-danger',
                    'data' => [
                        'confirm' => 'Ви справді хочете видалити цю публікацію?',
                        'method' => 'post',
                    ],
                ]) ?>
                <?= Html::a('До списку', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
            <?php else: ?>
                <?= Html::a('На головну', ['site/index'], ['class' => 'btn btn-outline-secondary']) ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4 p-4 bg-white mb-4">
        <?php if (!empty($model->image_path)): ?>
            <div class="mb-4 text-center">
                <?= Html::img('@web/' . ltrim($model->image_path, '/\\'), [
                    'alt' => Html::encode($model->title),
                    'class' => 'img-fluid rounded-3 mx-auto d-block',
                    'style' => 'max-height: 420px; object-fit: cover;',
                ]) ?>
            </div>
        <?php endif; ?>

        <div class="post-content" style="white-space: pre-line; line-height: 1.65;">
            <?= Html::encode($model->content) ?>
        </div>

        <?php if (!empty($model->tags)): ?>
            <div class="mt-4 pt-3 border-top">
                <span class="text-muted small me-2">Мітки:</span>
                <?php foreach ($model->tags as $tag): ?>
                    <span class="badge bg-secondary me-1"><?= Html::encode($tag->name) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

<div class="mt-4 pt-3 border-top">
    <div class="small text-muted fw-semibold mb-2">Поділитися публікацією у соціальних мережах:</div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <?= Html::a('Telegram', $telegramUrl, [
            'class' => 'btn btn-sm btn-outline-info',
            'target' => '_blank',
            'rel' => 'noopener noreferrer',
        ]) ?>
        <?= Html::a('Facebook', $facebookUrl, [
            'class' => 'btn btn-sm btn-outline-primary',
            'target' => '_blank',
            'rel' => 'noopener noreferrer',
        ]) ?>
        <?= Html::a('X (Twitter)', $twitterUrl, [
            'class' => 'btn btn-sm btn-outline-dark',
            'target' => '_blank',
            'rel' => 'noopener noreferrer',
        ]) ?>
    </div>
</div>

        <div class="mt-4 pt-3 border-top d-flex flex-wrap gap-2">
            <?= Html::a('Залишити відгук та оцінити', ['site/comment', 'id' => $model->id], ['class' => 'btn btn-outline-primary']) ?>
        </div>
    </div>

    <!-- Статистика оцінок та відгуків до статті -->
    <div class="card shadow-sm border-0 rounded-4 p-4 bg-white mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h4 fw-bold mb-0">Відгуки та статистика оцінювання</h2>
            <?= Html::a('+ Новий відгук', ['site/comment', 'id' => $model->id], ['class' => 'btn btn-sm btn-primary']) ?>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="p-3 rounded-3 bg-light border h-100">
                    <div class="small text-muted">Середня оцінка допису</div>
                    <div class="fw-bold fs-4 text-primary mt-1">
                        <?= $reviewStats['count'] > 0 ? Html::encode((string)$reviewStats['average']) . ' / 5' : '—' ?>
                    </div>
                    <div class="text-warning small"><?= Html::encode($reviewStats['stars']) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 rounded-3 bg-light border h-100">
                    <div class="small text-muted">Категорія середньої оцінки</div>
                    <div class="fw-bold fs-6 mt-1 text-capitalize"><?= Html::encode($reviewStats['category']) ?></div>
                    <div class="small text-muted mt-1">1–2: низька • 3: середня • 4–5: висока</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 rounded-3 bg-light border h-100">
                    <div class="small text-muted">Усього відгуків</div>
                    <div class="fw-bold fs-4 mt-1"><?= (int)$reviewStats['count'] ?></div>
                    <?php if ($canManage): ?>
                        <div class="small text-muted">На дод. перевірці: <strong><?= (int)$reviewStats['pendingCount'] ?></strong></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (empty($reviews)): ?>
            <div class="text-muted">До цього допису ще немає відгуків. Будьте першим, хто оцінить публікацію!</div>
        <?php else: ?>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($reviews as $review): ?>
                    <?php
                    $rScore = (int)$review->rating;
                    $rStars = str_repeat('★', max(1, min(5, $rScore))) . str_repeat('☆', 5 - max(1, min(5, $rScore)));
                    $rCategory = $rScore <= 2 ? 'низька' : ($rScore === 3 ? 'середня' : 'висока');
                    $isPassed = $review->moderation_status === 'preliminary_check_passed';
                    ?>
                    <div class="p-3 rounded-3 border bg-light">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <div>
                                <strong><?= Html::encode($review->author_name) ?></strong>
                                <span class="text-warning ms-2"><?= Html::encode($rStars) ?> (<?= $rScore ?>/5)</span>
                                <span class="badge bg-secondary ms-1"><?= Html::encode($rCategory) ?></span>
                            </div>
                            <div class="small text-muted">
                                <?= Html::encode(Yii::$app->formatter->asDatetime($review->created_at, 'php:d.m.Y H:i:s')) ?>
                            </div>
                        </div>

                        <div class="mb-2" style="white-space: pre-line;"><?= Html::encode($review->content) ?></div>

                        <?php if ($canManage): ?>
                            <div class="small">
                                Статус модерації:
                                <span class="badge <?= $isPassed ? 'bg-info text-dark' : 'bg-warning text-dark' ?>">
                                    <?= Html::encode($review->moderation_status) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Коментарі та відповіді користувачів -->
    <div id="comments-section" class="card shadow-sm border-0 rounded-4 p-4 bg-white">
        <h2 class="h4 fw-bold mb-3">Коментарі та відповіді</h2>

        <?php $form = ActiveForm::begin([
            'action' => ['site/add-comment', 'postId' => $model->id],
            'method' => 'post',
            'options' => ['class' => 'mb-4 pb-3 border-bottom'],
        ]); ?>
            <?= $form->field($newCommentForm, 'parent_id')->hiddenInput(['value' => ''])->label(false) ?>
            <div class="row g-2">
                <div class="col-md-4">
                    <?= $form->field($newCommentForm, 'author_name')->textInput([
                        'value' => $defaultAuthor,
                        'maxlength' => 50,
                        'placeholder' => 'Ваше імʼя',
                    ]) ?>
                </div>
                <div class="col-md-8">
                    <?= $form->field($newCommentForm, 'content')->textarea([
                        'rows' => 2,
                        'placeholder' => 'Напишіть коментар до публікації...',
                    ]) ?>
                </div>
            </div>
            <?= Html::submitButton('Опублікувати коментар', ['class' => 'btn btn-primary btn-sm mt-1']) ?>
        <?php ActiveForm::end(); ?>

        <?php if (empty($rootComments)): ?>
            <div class="text-muted">Коментарів ще немає.</div>
        <?php else: ?>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($rootComments as $comment): ?>
                    <?php $replies = $repliesByParent[(int)$comment->id] ?? []; ?>
                    <div class="p-3 rounded-3 border bg-light" id="comment-<?= (int)$comment->id ?>">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong><?= Html::encode($comment->author_name) ?></strong>
                            <span class="small text-muted">
                                <?= Html::encode(Yii::$app->formatter->asDatetime($comment->created_at, 'php:d.m.Y H:i')) ?>
                            </span>
                        </div>
                        <div class="mb-2" style="white-space: pre-line;"><?= Html::encode($comment->content) ?></div>

                        <button
                            type="button"
                            class="btn btn-link btn-sm p-0 text-decoration-none"
                            onclick="var box = document.getElementById('reply-box-<?= (int)$comment->id ?>'); box.classList.toggle('d-none');"
                        >
                            Відповісти
                        </button>

                        <div id="reply-box-<?= (int)$comment->id ?>" class="d-none mt-2 pt-2 border-top">
                            <?php $replyForm = ActiveForm::begin([
                                'action' => ['site/add-comment', 'postId' => $model->id],
                                'method' => 'post',
                            ]); ?>
                                <?= $replyForm->field($newCommentForm, 'parent_id')->hiddenInput([
                                    'id' => 'reply-parent-' . (int)$comment->id,
                                    'value' => (int)$comment->id,
                                ])->label(false) ?>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <?= $replyForm->field($newCommentForm, 'author_name')->textInput([
                                            'id' => 'reply-author-' . (int)$comment->id,
                                            'value' => $defaultAuthor,
                                            'maxlength' => 50,
                                            'placeholder' => 'Ваше імʼя',
                                        ])->label(false) ?>
                                    </div>
                                    <div class="col-md-8">
                                        <?= $replyForm->field($newCommentForm, 'content')->textInput([
                                            'id' => 'reply-content-' . (int)$comment->id,
                                            'placeholder' => 'Напишіть відповідь на коментар...',
                                        ])->label(false) ?>
                                    </div>
                                </div>
                                <?= Html::submitButton('Надіслати відповідь', ['class' => 'btn btn-outline-primary btn-sm mt-1']) ?>
                            <?php ActiveForm::end(); ?>
                        </div>

                        <?php if (!empty($replies)): ?>
                            <div class="mt-3 ps-3 ms-2 border-start border-2 d-flex flex-column gap-2">
                                <?php foreach ($replies as $reply): ?>
                                    <div class="p-2 rounded bg-white border" id="comment-<?= (int)$reply->id ?>">
                                        <div class="d-flex justify-content-between align-items-center small mb-1">
                                            <strong><?= Html::encode($reply->author_name) ?></strong>
                                            <span class="text-muted">
                                                <?= Html::encode(Yii::$app->formatter->asDatetime($reply->created_at, 'php:d.m.Y H:i')) ?>
                                            </span>
                                        </div>
                                        <div class="small" style="white-space: pre-line;"><?= Html::encode($reply->content) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>