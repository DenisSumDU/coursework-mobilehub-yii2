<?php

/** @var yii\web\View $this */
/** @var app\models\Post $model */
/** @var array $categories */
/** @var array $tags */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
?>

<div class="post-form card shadow-sm border-0 rounded-4 p-4">
    <?php $form = ActiveForm::begin([
        'id' => 'post-form',
        'options' => ['enctype' => 'multipart/form-data'],
    ]); ?>

        <?= $form->errorSummary($model, ['class' => 'alert alert-danger']) ?>

        <div class="row">
            <div class="col-md-8">
                <?= $form->field($model, 'title')->textInput([
                    'maxlength' => 150,
                    'placeholder' => 'Наприклад: Огляд нового смартфона з камерою 200 МП',
                ]) ?>
            </div>
            <div class="col-md-4">
                <?= $form->field($model, 'published_date')->input('date') ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'category_id')->dropDownList($categories, [
                    'prompt' => 'Оберіть категорію...',
                ]) ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'tagIds')->listBox($tags, [
                    'multiple' => true,
                    'size' => 4,
                    'class' => 'form-select',
                ]) ?>
            </div>
        </div>

        <?= $form->field($model, 'content')->textarea([
            'rows' => 10,
            'placeholder' => 'Напишіть детальний огляд смартфона (мінімум 10 слів). Зберігаються абзаци та перенесення рядків.',
        ]) ?>

        <div class="row align-items-center">
            <div class="col-md-7">
                <?= $form->field($model, 'imageFile')->fileInput([
                    'accept' => '.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp',
                ]) ?>
            </div>
            <div class="col-md-5">
                <?php if (!empty($model->image_path)): ?>
                    <div class="mt-2">
                        <div class="small text-muted mb-1">Поточне зображення:</div>
                        <?= Html::img('@web/' . ltrim($model->image_path, '/\\'), [
                            'alt' => Html::encode($model->title),
                            'class' => 'img-thumbnail rounded-3',
                            'style' => 'max-height: 120px;',
                        ]) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group mt-4 d-flex gap-2">
            <?= Html::submitButton(
                $model->isNewRecord ? 'Створити публікацію' : 'Зберегти зміни',
                ['class' => 'btn btn-primary px-4']
            ) ?>
            <?= Html::a('Скасувати', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>

    <?php ActiveForm::end(); ?>
</div>