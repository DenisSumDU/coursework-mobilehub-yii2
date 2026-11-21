<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use yii\bootstrap5\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;

AppAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">
<head>
    <title><?= Html::encode($this->title) ?> — MobileHub</title>
    <?php $this->head() ?>
</head>
<body class="d-flex flex-column h-100 mobile-hub-body">
<?php $this->beginBody() ?>

<header id="header">
    <?php
    NavBar::begin([
        'brandLabel' => '<span class="brand-glow-dot"></span><span class="brand-text">Mobile<span class="brand-accent">Hub</span></span>',
        'brandUrl' => Yii::$app->homeUrl,
        'options' => [
            'class' => 'navbar navbar-expand-lg navbar-dark mobile-navbar fixed-top',
        ],
    ]);

    $identity = Yii::$app->user->identity;
    $isAdmin = !Yii::$app->user->isGuest && $identity !== null && (
        (isset($identity->role) && in_array($identity->role, ['admin', 'author'], true))
        || (isset($identity->username) && $identity->username === 'admin_author')
    );

    $menuItems = [
        ['label' => 'Головна', 'url' => ['/site/index']],
    ];

    if ($isAdmin) {
        $menuItems[] = ['label' => 'Адмін панель', 'url' => ['/post/index']];
    }

    if (Yii::$app->user->isGuest) {
        $menuItems[] = ['label' => 'Реєстрація', 'url' => ['/site/signup']];
        $menuItems[] = ['label' => 'Увійти', 'url' => ['/site/login']];
    } else {
        $menuItems[] = [
            'label' => 'Вийти (' . Html::encode($identity->username) . ')',
            'url' => ['/site/logout'],
            'linkOptions' => ['data-method' => 'post', 'class' => 'nav-link logout-link'],
        ];
    }

    echo Nav::widget([
        'options' => ['class' => 'navbar-nav ms-auto align-items-lg-center gap-lg-1'],
        'items' => $menuItems,
        'encodeLabels' => false,
    ]);

    NavBar::end();
    ?>
</header>

<main id="main" class="flex-shrink-0" role="main">
    <div class="container mobile-main-container">
        <?php if (!empty($this->params['breadcrumbs'])): ?>
            <?= Breadcrumbs::widget([
                'homeLink' => ['label' => 'Головна', 'url' => Yii::$app->homeUrl],
                'links' => $this->params['breadcrumbs'],
                'options' => ['class' => 'mobile-breadcrumbs'],
            ]) ?>
        <?php endif ?>

        <?= Alert::widget([
            'options' => ['class' => 'mb-3'],
            'closeButton' => ['label' => '', 'class' => 'btn-close'],
        ]) ?>
        <?= $content ?>
    </div>
</main>

<footer id="footer" class="mt-auto mobile-footer">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <strong>MobileHub</strong> — Блог про мобільні телефони, огляди та тестування смартфонів.
        </div>
        <div class="text-muted small">
            &copy; <?= date('Y') ?> • Створено на Yii2
        </div>
    </div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>