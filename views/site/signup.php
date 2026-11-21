<?php

/** @var yii\web\View $this */
/** @var app\models\SignupForm $model */

use yii\widgets\ActiveForm;
use yii\helpers\Html;

$this->title = 'Реєстрація користувача';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="site-signup py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="signup-wrapper">
                <div class="row g-0">
                    <div class="col-md-7 signup-form-panel">
                        <div class="signup-badge">MobileHub • Спільнота смартфонів</div>
                        <h1 class="signup-title"><?= Html::encode($this->title) ?></h1>
                        <p class="signup-subtitle">
                            Створіть обліковий запис, щоб залишати коментарі до оглядів смартфонів, оцінювати моделі та брати участь в обговореннях.
                        </p>

                        <?php $form = ActiveForm::begin([
                            'id' => 'form-signup',
                            'fieldConfig' => [
                                'template' => "{label}\n{input}\n{error}",
                                'labelOptions' => ['class' => 'form-label signup-label'],
                                'inputOptions' => ['class' => 'form-control signup-input'],
                                'errorOptions' => ['class' => 'invalid-feedback d-block signup-error'],
                            ],
                        ]); ?>

                            <?= $form->field($model, 'username')->textInput([
                                'autofocus' => true,
                                'placeholder' => 'Наприклад: tech_fan_2026',
                            ]) ?>

                            <?= $form->field($model, 'email')->textInput([
                                'placeholder' => 'name@example.com',
                            ]) ?>

                            <?= $form->field($model, 'password')->passwordInput([
                                'placeholder' => 'Мінімум 6 символів',
                            ]) ?>

                            <?= $form->field($model, 'password_repeat')->passwordInput([
                                'placeholder' => 'Повторіть пароль ще раз',
                            ]) ?>

                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-4">
                                <?= Html::submitButton('Створити акаунт', [
                                    'class' => 'btn btn-primary signup-btn',
                                    'name' => 'signup-button',
                                ]) ?>

                                <span class="signup-login-hint">
                                    Вже маєте акаунт?
                                    <?= Html::a('Увійти', ['/site/login'], ['class' => 'signup-link']) ?>
                                </span>
                            </div>

                        <?php ActiveForm::end(); ?>
                    </div>

                    <div class="col-md-5 signup-aside-panel">
                        <div class="signup-aside-content">
                            <div class="phone-mockup">
                                <div class="phone-notch"></div>
                                <div class="phone-screen">
                                    <div class="phone-tag">ОГЛЯДИ ТА ТЕСТИ</div>
                                    <div class="phone-heading">Світ мобільних технологій</div>
                                    <ul class="signup-benefits">
                                        <li>Порівняння флагманів, камер та автономності</li>
                                        <li>Чесні відгуки та оцінки реальних власників</li>
                                        <li>Зручне обговорення новинок і оновлень ОС</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>