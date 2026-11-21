<?php

/** @var yii\web\View $this */
/** @var array $postStatsList */
/** @var array $rankingList */

use yii\helpers\Html;

$this->title = 'Статистика відгуків та рейтинг публікацій';
$this->params['breadcrumbs'][] = ['label' => 'Адмін панель публікацій', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$totalAllReviews = array_sum(array_map(static fn(array $item) => (int)$item['count'], $postStatsList));
$ratedPostsCount = count(array_filter($postStatsList, static fn(array $item) => $item['avg'] !== null));
?>

<div class="post-stats py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h2 fw-bold mb-1"><?= Html::encode($this->title) ?></h1>
            <p class="text-muted mb-0">

            </p>
        </div>
        <div class="d-flex gap-2">
            <?= Html::a('До списку публікацій', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
    </div>

    <!-- Загальні показники -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="small text-muted">Усього публікацій</div>
                <div class="h3 fw-bold mb-0 mt-1"><?= count($postStatsList) ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="small text-muted">Публікацій з оцінками</div>
                <div class="h3 fw-bold mb-0 mt-1"><?= (int)$ratedPostsCount ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="small text-muted">Усього відгуків у БД</div>
                <div class="h3 fw-bold mb-0 mt-1"><?= (int)$totalAllReviews ?></div>
            </div>
        </div>
    </div>

    <!-- 1. Рейтинг публікацій за середньою оцінкою -->
    <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 bg-white mb-4">
        <h2 class="h4 fw-bold mb-3">Рейтинг публікацій за середньою оцінкою</h2>

        <?php if (empty($rankingList)): ?>
            <div class="text-muted">Публікацій поки немає.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 70px;">Місце</th>
                            <th>Публікація</th>
                            <th>Середня оцінка</th>
                            <th>Категорія</th>
                            <th>Кількість відгуків</th>
                            <th>Мін / Макс</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rank = 0; ?>
                        <?php foreach ($rankingList as $item): ?>
                            <?php
                            /** @var \app\models\Post $post */
                            $post = $item['post'];
                            $hasReviews = $item['avg'] !== null && (int)$item['count'] > 0;
                            if ($hasReviews) {
                                $rank++;
                            }
                            ?>
                            <tr>
                                <td class="fw-bold">
                                    <?= $hasReviews ? '#' . $rank : '<span class="text-muted">—</span>' ?>
                                </td>
                                <td>
                                    <?= Html::a(Html::encode($post->title), ['view', 'id' => $post->id], ['class' => 'fw-semibold text-decoration-none']) ?>
                                    <?php if ($post->category): ?>
                                        <div class="small text-muted"><?= Html::encode($post->category->name) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($hasReviews): ?>
                                        <span class="fw-bold fs-6 text-primary"><?= Html::encode((string)$item['avg']) ?> / 5</span>
                                    <?php else: ?>
                                        <span class="text-muted">Відгуків ще немає</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $hasReviews ? 'bg-secondary' : 'bg-light text-muted border' ?>">
                                        <?= Html::encode($item['category']) ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?= (int)$item['count'] ?></strong>
                                    <span class="small text-muted">
                                        <?= (int)$item['count'] === 1 ? 'відгук' : (((int)$item['count'] >= 2 && (int)$item['count'] <= 4) ? 'відгуки' : 'відгуків') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($hasReviews): ?>
                                        <span class="small">мін: <strong><?= (int)$item['min'] ?></strong> / макс: <strong><?= (int)$item['max'] ?></strong></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- 2. Детальна статистика та розподіл оцінок 1..5 для кожної публікації -->
    <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 bg-white">
        <h2 class="h4 fw-bold mb-3">Детальна статистика та розподіл оцінок (1–5) по публікаціях</h2>

        <?php if (empty($postStatsList)): ?>
            <div class="text-muted">Публікацій поки немає.</div>
        <?php else: ?>
            <div class="d-flex flex-column gap-4">
                <?php foreach ($postStatsList as $item): ?>
                    <?php
                    /** @var \app\models\Post $post */
                    $post = $item['post'];
                    $count = (int)$item['count'];
                    $hasReviews = $item['avg'] !== null && $count > 0;
                    $dist = $item['distribution'];
                    ?>
                    <div class="p-3 p-md-4 rounded-3 border bg-light">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <h3 class="h5 fw-bold mb-1">
                                    <?= Html::a(Html::encode($post->title), ['view', 'id' => $post->id], ['class' => 'text-dark text-decoration-none']) ?>
                                </h3>
                                <div class="small text-muted">
                                    Дата публікації: <strong><?= Html::encode($post->published_date) ?></strong>
                                    <?php if ($post->category): ?>
                                        • Категорія: <strong><?= Html::encode($post->category->name) ?></strong>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <?= Html::a('Переглянути допис', ['view', 'id' => $post->id], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
                                <?= Html::a('Додати відгук', ['site/comment', 'id' => $post->id], ['class' => 'btn btn-sm btn-outline-primary']) ?>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6 col-md-3">
                                <div class="p-2 rounded bg-white border">
                                    <div class="small text-muted">Усього відгуків</div>
                                    <div class="fw-bold fs-5"><?= $count ?></div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-2 rounded bg-white border">
                                    <div class="small text-muted">Середня оцінка</div>
                                    <div class="fw-bold fs-5 text-primary">
                                        <?= $hasReviews ? Html::encode((string)$item['avg']) . ' / 5' : 'Відгуків ще немає' ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-2 rounded bg-white border">
                                    <div class="small text-muted">Найнижча оцінка</div>
                                    <div class="fw-bold fs-5"><?= $hasReviews ? (int)$item['min'] . ' ★' : '—' ?></div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-2 rounded bg-white border">
                                    <div class="small text-muted">Найвища оцінка</div>
                                    <div class="fw-bold fs-5"><?= $hasReviews ? (int)$item['max'] . ' ★' : '—' ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Таблиця розподілу оцінок 1..5 -->
                        <div class="table-responsive bg-white rounded border">
                            <table class="table table-sm table-bordered mb-0 text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th>Оцінка</th>
                                        <th>1 ★</th>
                                        <th>2 ★★</th>
                                        <th>3 ★★★</th>
                                        <th>4 ★★★★</th>
                                        <th>5 ★★★★★</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="fw-semibold text-start ps-2">Кількість відгуків</td>
                                        <?php for ($s = 1; $s <= 5; $s++): ?>
                                            <td><strong><?= (int)($dist[$s] ?? 0) ?></strong></td>
                                        <?php endfor; ?>
                                    </tr>
                                    <tr>
                                        <td class="text-muted text-start ps-2">Частка (%)</td>
                                        <?php for ($s = 1; $s <= 5; $s++): ?>
                                            <?php $pct = $count > 0 ? round(((int)($dist[$s] ?? 0) / $count) * 100, 1) : 0.0; ?>
                                            <td class="small text-muted"><?= $pct ?>%</td>
                                        <?php endfor; ?>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>