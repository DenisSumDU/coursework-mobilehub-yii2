<?php

/** @var yii\web\View $this */
/** @var app\models\Post $post */
/** @var app\models\CommentForm $model */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Відгук до допису: ' . $post->title;
$this->params['breadcrumbs'][] = ['label' => 'Огляди', 'url' => ['site/index']];
$this->params['breadcrumbs'][] = ['label' => $post->title, 'url' => ['post/view', 'id' => $post->id]];
$this->params['breadcrumbs'][] = 'Відгук та оцінка';

$selectedRating = (int)$model->rating;
?>

<div class="site-comment">
    <div class="card shadow-sm border-0 rounded-4 p-4 mb-4">
        <div class="text-muted small mb-1">Допис блогу</div>
        <h1 class="h3 fw-bold mb-2"><?= Html::encode($post->title) ?></h1>
        <p class="text-secondary mb-0">
            <?= nl2br(Html::encode((string)$post->content)) ?>
        </p>
    </div>

    <div class="card shadow-sm border-0 rounded-4 p-4">
        <h2 class="h4 fw-bold mb-3">Залишити відгук та оцінити допис</h2>

        <?php $form = ActiveForm::begin([
            'id' => 'comment-form',
        ]); ?>

            <?= $form->errorSummary($model, ['class' => 'alert alert-danger']) ?>

            <?= $form->field($model, 'authorName')->textInput([
                'maxlength' => 50,
                'placeholder' => 'Введіть ваше імʼя (від 2 до 50 символів)',
            ]) ?>

            <div class="mb-3">
                <label class="form-label d-block fw-semibold"><?= Html::encode($model->getAttributeLabel('rating')) ?></label>
                <div class="star-rating-picker d-inline-flex flex-row-reverse justify-content-end gap-1" role="radiogroup" aria-label="Оцінка від 1 до 5 зірок">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <?php $inputId = 'rating-star-' . $i; ?>
                        <input
                            type="radio"
                            class="btn-check"
                            name="CommentForm[rating]"
                            id="<?= $inputId ?>"
                            value="<?= $i ?>"
                            <?= $selectedRating === $i ? 'checked' : '' ?>
                        >
                        <label class="btn btn-outline-warning fs-4 px-2 py-1" for="<?= $inputId ?>" title="<?= $i ?> з 5">
                            ★
                        </label>
                    <?php endfor; ?>
                </div>
                <?= $form->field($model, 'rating', [
                    'template' => '{error}',
                ])->hiddenInput() ?>
            </div>

            <?= $form->field($model, 'content')->textarea([
                'rows' => 6,
                'maxlength' => 1000,
                'placeholder' => 'Напишіть ваш відгук (від 10 до 1000 символів)...',
            ]) ?>

            <div class="d-flex flex-wrap gap-2 mt-4">
                <?= Html::submitButton('Надіслати відгук', ['class' => 'btn btn-primary px-4']) ?>
                <?= Html::a('До перегляду допису', ['post/view', 'id' => $post->id], ['class' => 'btn btn-outline-secondary']) ?>
            </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>