<?php

/** @var yii\web\View $this */
/** @var app\models\Post $model */
/** @var array $categories */
/** @var array $tags */

use yii\helpers\Html;

$this->title = 'Створення публікації';
$this->params['breadcrumbs'][] = ['label' => 'Адмін панель публікацій', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="post-create py-3">
    <h1 class="h2 mb-3"><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'categories' => $categories,
        'tags' => $tags,
    ]) ?>
</div>