<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var app\models\Category[] $categories */
/** @var app\models\Tag[] $tags */
/** @var app\models\Category|null $activeCategory */
/** @var app\models\Tag|null $activeTag */

use yii\bootstrap5\LinkPager;
use yii\helpers\Html;
use yii\helpers\StringHelper;

$this->title = 'Огляди та новини смартфонів';
$posts = $dataProvider->getModels();
$isGuest = Yii::$app->user->isGuest;
?>

<div class="site-index">
    <!-- Герой-блок із підсвіткою -->
    <section class="hero-banner mb-4">
        <div class="hero-glow"></div>
        <div class="row align-items-center g-4 position-relative">
            <div class="col-lg-8">
                <div class="hero-pill mb-2">📱 Світ мобільних технологій</div>
                <h1 class="hero-title mb-2">Огляди смартфонів, камер та автономності</h1>
                <p class="hero-subtitle mb-3">
                    Актуальні тести флагманів, бюджетних моделей і новинок ринку мобільних телефонів із детальним розбором характеристик.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <?php if (!$isGuest): ?>
                        <?= Html::a('Керувати публікаціями', ['/post/index'], ['class' => 'btn btn-primary signup-btn']) ?>
                        <?= Html::a('+ Додати огляд', ['/post/create'], ['class' => 'btn btn-hero-ghost']) ?>
                    <?php else: ?>
                        <?= Html::a('Увійти', ['/site/login'], ['class' => 'btn btn-primary signup-btn']) ?>
                        <?= Html::a('Реєстрація', ['/site/signup'], ['class' => 'btn btn-hero-ghost']) ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="hero-stats-box">
                    <div class="hero-stat-item">
                        <span class="hero-stat-value"><?= $dataProvider->getTotalCount() ?></span>
                        <span class="hero-stat-label">Публікацій у стрічці</span>
                    </div>
                    <div class="hero-stat-item">
                        <span class="hero-stat-value"><?= count($categories) ?></span>
                        <span class="hero-stat-label">Категорій пристроїв</span>
                    </div>
                    <div class="hero-stat-item">
                        <span class="hero-stat-value"><?= count($tags) ?></span>
                        <span class="hero-stat-label">Тематичних міток</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Фільтри за категоріями та мітками -->
    <section class="filter-bar mb-4">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <span class="filter-label">Категорії:</span>
            <?= Html::a('Усі', ['/site/index'], [
                'class' => 'filter-chip ' . (!$activeCategory && !$activeTag ? 'is-active' : ''),
            ]) ?>
            <?php foreach ($categories as $cat): ?>
                <?= Html::a(Html::encode($cat->name), ['/site/index', 'category' => $cat->id], [
                    'class' => 'filter-chip ' . ($activeCategory && (int)$activeCategory->id === (int)$cat->id ? 'is-active' : ''),
                ]) ?>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($tags)): ?>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="filter-label">Мітки:</span>
                <?php foreach ($tags as $t): ?>
                    <?= Html::a('#' . Html::encode($t->name), ['/site/index', 'tag' => $t->id], [
                        'class' => 'tag-chip ' . ($activeTag && (int)$activeTag->id === (int)$t->id ? 'is-active' : ''),
                    ]) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Стрічка публікацій -->
    <?php if (empty($posts)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
            <h3 class="h5 mb-2">Публікацій за обраним фільтром поки немає</h3>
            <p class="text-muted mb-3">
                <?= !$isGuest
                    ? 'Створіть перший огляд смартфона або скиньте фільтр.'
                    : 'Спробуйте обрати іншу категорію, мітку або скинути фільтр.' ?>
            </p>
            <div>
                <?php if (!$isGuest): ?>
                    <?= Html::a('Створити публікацію', ['/post/create'], ['class' => 'btn btn-primary signup-btn']) ?>
                <?php else: ?>
                    <?= Html::a('Показати всі огляди', ['/site/index'], ['class' => 'btn btn-primary signup-btn']) ?>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($posts as $post): ?>
                <?php
                $hasImage = false;
                $webImage = null;
                if (!empty($post->image_path)) {
                    $cleanRel = ltrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $post->image_path), DIRECTORY_SEPARATOR);
                    if (is_file(Yii::getAlias('@webroot/' . $cleanRel))) {
                        $hasImage = true;
                        $webImage = '@web/' . ltrim(str_replace('\\', '/', $post->image_path), '/');
                    }
                }
                $excerpt = StringHelper::truncateWords(strip_tags((string)$post->content), 24, '...');
                ?>
                <div class="col-md-6 col-lg-4">
                    <article class="post-card h-100 d-flex flex-column">
                        <div class="post-card-media">
                            <?php if ($hasImage): ?>
                                <?= Html::a(
                                    Html::img($webImage, [
                                        'alt' => Html::encode($post->title),
                                        'class' => 'post-card-img',
                                    ]),
                                    ['/post/view', 'id' => $post->id]
                                ) ?>
                            <?php else: ?>
                                <div class="post-card-placeholder">
                                    <span>📱 <?= Html::encode($post->category ? $post->category->name : 'Смартфон') ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="post-card-body d-flex flex-column flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-2 small">
                                <span class="post-category-badge">
                                    <?= Html::encode($post->category ? $post->category->name : 'Без категорії') ?>
                                </span>
                                <span class="text-muted">
                                    <?= Yii::$app->formatter->asDate($post->published_date, 'php:d.m.Y') ?>
                                </span>
                            </div>

                            <h2 class="post-card-title h5 mb-2">
                                <?= Html::a(Html::encode($post->title), ['/post/view', 'id' => $post->id], [
                                    'class' => 'stretched-link text-decoration-none',
                                ]) ?>
                            </h2>

                            <p class="post-card-excerpt text-muted small flex-grow-1 mb-3">
                                <?= Html::encode($excerpt) ?>
                            </p>

                            <?php if (!empty($post->tags)): ?>
                                <div class="mb-3 position-relative" style="z-index: 2;">
                                    <?php foreach ($post->tags as $tagItem): ?>
                                        <?= Html::a('#' . Html::encode($tagItem->name), ['/site/index', 'tag' => $tagItem->id], [
                                            'class' => 'mini-tag me-1',
                                        ]) ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="post-card-footer d-flex justify-content-between align-items-center pt-2 border-top position-relative" style="z-index: 2;">
                                <span class="small text-secondary">
                                    <?= Html::encode($post->author ? $post->author->username : 'Редакція') ?>
                                </span>
                                <div class="d-flex gap-2">
                                    <?= Html::a('Читати', ['/post/view', 'id' => $post->id], ['class' => 'btn btn-sm btn-outline-primary rounded-pill px-3']) ?>
                                    <?php if (!$isGuest): ?>
                                        <?= Html::a('Змінити', ['/post/update', 'id' => $post->id], ['class' => 'btn btn-sm btn-outline-secondary rounded-pill px-2']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-4 d-flex justify-content-center">
            <?= LinkPager::widget([
                'pagination' => $dataProvider->pagination,
                'options' => ['class' => 'pagination mobile-pagination mb-0'],
                'prevPageLabel' => '&laquo;',
                'nextPageLabel' => '&raquo;',
            ]) ?>
        </div>
    <?php endif; ?>
</div>