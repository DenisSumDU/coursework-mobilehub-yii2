<?php

/** @var yii\web\View $this */
/** @var app\models\PostSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $reviewStatsByPost */

use app\models\Category;
use app\models\Review;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\StringHelper;

$this->title = 'Адмін панель публікацій — Мобільні телефони';
$this->params['breadcrumbs'][] = $this->title;

$reviewStatsByPost = $reviewStatsByPost ?? [];

$categoriesFilter = Category::find()
    ->select(['name', 'id'])
    ->orderBy(['name' => SORT_ASC])
    ->indexBy('id')
    ->column();

$latestReviews = Review::find()
    ->with('post')
    ->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC])
    ->limit(10)
    ->all();
?>

<div class="post-index py-3">
    <div class="admin-header-card d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="signup-badge mb-2">MobileHub • Керування контентом</div>
            <h1 class="h2 mb-1 fw-bold text-dark"><?= Html::encode($this->title) ?></h1>
            <p class="text-muted mb-0">Створення, перегляд, редагування та видалення оглядів смартфонів</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?= Html::a('Статистика відгуків', ['stats'], ['class' => 'btn btn-outline-primary']) ?>
            <?= Html::a('+ Нова публікація', ['create'], ['class' => 'btn btn-primary signup-btn']) ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 bg-white mb-4">
        <div class="table-responsive">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'summary' => 'Показано <b>{begin}–{end}</b> із <b>{totalCount}</b> публікацій',
                'emptyText' => 'Публікацій не знайдено.',
                'tableOptions' => ['class' => 'table table-hover align-middle mb-0 admin-posts-table'],
                'columns' => [
                    [
                        'class' => 'yii\grid\SerialColumn',
                        'header' => '№',
                        'contentOptions' => ['style' => 'width: 48px; color: #64748b; font-weight: 600;'],
                    ],

                    [
                        'attribute' => 'image_path',
                        'label' => 'Фото',
                        'format' => 'raw',
                        'filter' => false,
                        'contentOptions' => ['style' => 'width: 96px;'],
                        'value' => static function ($model) {
                            if (empty($model->image_path)) {
                                return '<span class="no-photo-badge">Без фото</span>';
                            }

                            $cleanRelPath = ltrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $model->image_path), DIRECTORY_SEPARATOR);
                            $fullPath = Yii::getAlias('@webroot/' . $cleanRelPath);

                            if (!is_file($fullPath)) {
                                return '<span class="no-photo-badge" title="' . Html::encode($model->image_path) . '">Немає файлу</span>';
                            }

                            $webUrl = '@web/' . ltrim(str_replace('\\', '/', $model->image_path), '/');
                            return Html::img($webUrl, [
                                'alt' => Html::encode($model->title),
                                'class' => 'admin-post-thumb',
                            ]);
                        },
                    ],

                    [
                        'attribute' => 'title',
                        'format' => 'raw',
                        'value' => static function ($model) {
                            $excerpt = StringHelper::truncateWords(strip_tags((string)$model->content), 16, '...');
                            return '<div class="fw-semibold mb-1">'
                                . Html::a(Html::encode($model->title), ['view', 'id' => $model->id], ['class' => 'text-dark text-decoration-none admin-post-link'])
                                . '</div>'
                                . '<div class="small text-muted">' . Html::encode($excerpt) . '</div>';
                        },
                    ],

                    [
                        'attribute' => 'category_id',
                        'label' => 'Категорія',
                        'filter' => $categoriesFilter,
                        'filterInputOptions' => [
                            'class' => 'form-select form-select-sm',
                            'prompt' => 'Усі категорії',
                        ],
                        'value' => static fn($model) => $model->category
                            ? '<span class="badge rounded-pill text-bg-light border text-dark px-3 py-2">' . Html::encode($model->category->name) . '</span>'
                            : '<span class="text-muted">—</span>',
                        'format' => 'raw',
                    ],

                    [
                        'label' => 'Оцінки та відгуки',
                        'format' => 'raw',
                        'contentOptions' => ['style' => 'min-width: 185px;'],
                        'value' => static function ($model) use ($reviewStatsByPost) {
                            $stat = $reviewStatsByPost[(int)$model->id] ?? [
                                'count' => 0,
                                'avg' => 0.0,
                                'category' => 'немає оцінок',
                                'pending' => 0,
                            ];

                            if ((int)$stat['count'] === 0) {
                                return '<span class="text-muted small">Немає оцінок</span>'
                                    . '<div class="mt-1">' . Html::a('Переглянути', ['view', 'id' => $model->id], ['class' => 'small']) . '</div>';
                            }

                            $filled = max(1, min(5, (int)round((float)$stat['avg'])));
                            $stars = str_repeat('★', $filled) . str_repeat('☆', 5 - $filled);

                            $html = '<div class="text-warning small">' . Html::encode($stars) . '</div>'
                                . '<div><strong>' . Html::encode((string)$stat['avg']) . ' / 5</strong> '
                                . '<span class="badge bg-secondary">' . Html::encode($stat['category']) . '</span></div>'
                                . '<div class="small text-muted mt-1">Відгуків: <strong>' . (int)$stat['count'] . '</strong>';

                            if ((int)$stat['pending'] > 0) {
                                $html .= ' • <span class="text-warning fw-semibold">pending_review: ' . (int)$stat['pending'] . '</span>';
                            }

                            $html .= '</div>'
                                . '<div class="mt-1">' . Html::a('Усі відгуки статті', ['view', 'id' => $model->id], ['class' => 'small']) . '</div>';

                            return $html;
                        },
                    ],

                    [
                        'attribute' => 'published_date',
                        'format' => ['date', 'php:d.m.Y'],
                        'contentOptions' => ['style' => 'width: 135px; white-space: nowrap;'],
                        'filterInputOptions' => [
                            'class' => 'form-control form-control-sm',
                            'placeholder' => 'РРРР-ММ-ДД',
                        ],
                    ],

                    [
                        'class' => ActionColumn::class,
                        'header' => 'Дії',
                        'contentOptions' => ['style' => 'width: 115px; white-space: nowrap; text-align: center;'],
                        'buttonOptions' => ['class' => 'btn btn-sm btn-outline-secondary mx-0'],
                        'template' => '<div class="d-inline-flex gap-1">{view} {update} {delete}</div>',
                    ],
                ],
            ]) ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 bg-white">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h5 fw-bold mb-0">Останні відгуки до публікацій</h2>
            <span class="text-muted small">Показано останні <?= count($latestReviews) ?> записів із БД</span>
        </div>

        <?php if (empty($latestReviews)): ?>
            <div class="text-muted">Відгуків ще немає.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Допис</th>
                            <th>Автор</th>
                            <th>Оцінка</th>
                            <th>Категорія</th>
                            <th>Відгук</th>
                            <th>Модерація</th>
                            <th>Дата</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($latestReviews as $review): ?>
                            <?php
                            $score = (int)$review->rating;
                            $stars = str_repeat('★', max(1, min(5, $score))) . str_repeat('☆', 5 - max(1, min(5, $score)));
                            $category = $score <= 2 ? 'низька' : ($score === 3 ? 'середня' : 'висока');
                            $isPassed = $review->moderation_status === 'preliminary_check_passed';
                            $postStat = $reviewStatsByPost[(int)$review->post_id] ?? null;
                            ?>
                            <tr>
                                <td>
                                    <?php if ($review->post): ?>
                                        <?= Html::a(Html::encode($review->post->title), ['view', 'id' => $review->post->id], ['class' => 'fw-semibold text-decoration-none']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">#<?= (int)$review->post_id ?></span>
                                    <?php endif; ?>
                                    <?php if ($postStat && (int)$postStat['count'] > 0): ?>
                                        <div class="small text-muted">Сер. оцінка статті: <strong><?= Html::encode((string)$postStat['avg']) ?>/5</strong> (<?= Html::encode($postStat['category']) ?>)</div>
                                    <?php endif; ?>
                                </td>
                                <td><?= Html::encode($review->author_name) ?></td>
                                <td class="text-warning text-nowrap"><?= Html::encode($stars) ?> (<?= $score ?>/5)</td>
                                <td><span class="badge bg-secondary"><?= Html::encode($category) ?></span></td>
                                <td style="max-width: 320px;">
                                    <div class="small" style="white-space: pre-line;"><?= Html::encode(StringHelper::truncateWords((string)$review->content, 20, '...')) ?></div>
                                </td>
                                <td class="text-nowrap">
                                    <span class="badge <?= $isPassed ? 'bg-info text-dark' : 'bg-warning text-dark' ?>">
                                        <?= Html::encode($review->moderation_status) ?>
                                    </span>
                                </td>
                                <td class="small text-muted text-nowrap">
                                    <?= Html::encode(Yii::$app->formatter->asDatetime($review->created_at, 'php:d.m.Y H:i')) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>