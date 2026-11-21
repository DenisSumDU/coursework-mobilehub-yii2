<?php

/** @var yii\web\View $this */
/** @var app\models\LoginForm $model */

use yii\widgets\ActiveForm;
use yii\helpers\Html;

$this->title = 'Вхід в акаунт';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="site-login py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="signup-wrapper">
                <div class="row g-0">
                    <div class="col-md-7 signup-form-panel">
                        <div class="signup-badge">MobileHub • Спільнота смартфонів</div>
                        <h1 class="signup-title"><?= Html::encode($this->title) ?></h1>
                        <p class="signup-subtitle">
                            Увійдіть у свій обліковий запис, щоб залишати коментарі, оцінювати смартфони та керувати публікаціями.
                        </p>

                        <?php $form = ActiveForm::begin([
                            'id' => 'login-form',
                            'fieldConfig' => [
                                'template' => "{label}\n{input}\n{error}",
                                'labelOptions' => ['class' => 'form-label signup-label'],
                                'inputOptions' => ['class' => 'form-control signup-input'],
                                'errorOptions' => ['class' => 'invalid-feedback d-block signup-error'],
                            ],
                        ]); ?>

                            <?= $form->field($model, 'username')->textInput([
                                'autofocus' => true,
                                'placeholder' => 'Введіть ваше ім*я',
                            ]) ?>

                            <?= $form->field($model, 'password')->passwordInput([
                                'placeholder' => 'Введіть пароль',
                            ]) ?>

                            <div class="mb-3">
                                <?= $form->field($model, 'rememberMe')->checkbox([
                                    'template' => "<div class=\"form-check\">{input} {label}</div>\n{error}",
                                    'class' => 'form-check-input',
                                    'labelOptions' => ['class' => 'form-check-label signup-label'],
                                ]) ?>
                            </div>

                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-4">
                                <?= Html::submitButton('Увійти', [
                                    'class' => 'btn btn-primary signup-btn',
                                    'name' => 'login-button',
                                ]) ?>

                                <span class="signup-login-hint">
                                    Ще немає акаунта?
                                    <?= Html::a('Зареєструватися', ['/site/signup'], ['class' => 'signup-link']) ?>
                                </span>
                            </div>

                        <?php ActiveForm::end(); ?>
                    </div>

                    <div class="col-md-5 signup-aside-panel">
                        <div class="signup-aside-content">
                            <div class="phone-mockup">
                                <div class="phone-notch"></div>
                                <div class="phone-screen">
                                    <div class="phone-tag">ОСОБИСТИЙ КАБІНЕТ</div>
                                    <div class="phone-heading">Повертайтесь до оглядів</div>
                                    <ul class="signup-benefits">
                                        <li>Свіжі тести камер і процесорів</li>
                                        <li>Ваші оцінки та відгуки про моделі</li>
                                        <li>Швидкий доступ до обговорень</li>
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