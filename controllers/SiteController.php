<?php

namespace app\controllers;

use app\models\Comment;
use app\models\CommentForm;
use app\models\ContactForm;
use app\models\LoginForm;
use app\models\Post;
use app\models\Review;
use app\models\SignupForm;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage with smartphone reviews.
     *
     * @return string
     */
    public function actionIndex($category = null, $tag = null)
    {
        $query = Post::find()
            ->with(['category', 'author', 'tags'])
            ->orderBy(['published_date' => SORT_DESC, 'id' => SORT_DESC]);

        $activeCategory = null;
        if ($category !== null && $category !== '') {
            $activeCategory = \app\models\Category::findOne(['id' => (int)$category]);
            if ($activeCategory) {
                $query->andWhere(['category_id' => $activeCategory->id]);
            }
        }

        $activeTag = null;
        if ($tag !== null && $tag !== '') {
            $activeTag = \app\models\Tag::findOne(['id' => (int)$tag]);
            if ($activeTag) {
                $query->innerJoinWith('tags')->andWhere(['{{%tags}}.id' => $activeTag->id]);
            }
        }

        $dataProvider = new \yii\data\ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 6,
            ],
        ]);

        $categories = \app\models\Category::find()->orderBy(['name' => SORT_ASC])->all();
        $tags = \app\models\Tag::find()->orderBy(['name' => SORT_ASC])->all();

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'categories' => $categories,
            'tags' => $tags,
            'activeCategory' => $activeCategory,
            'activeTag' => $activeTag,
        ]);
    }

    /**
     * Displays a post and handles rating + review submission with preliminary moderation.
     *
     * @param int $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionComment($id)
    {
        $post = Post::findOne(['id' => (int)$id]);
        if ($post === null) {
            throw new NotFoundHttpException('Запитуваний допис блогу не знайдено.');
        }

        $model = new CommentForm();
        if (!Yii::$app->user->isGuest && Yii::$app->user->identity && empty($model->authorName)) {
            $model->authorName = (string)Yii::$app->user->identity->username;
        }

        if ($this->request->isPost && $model->load($this->request->post()) && $model->validate()) {
            $numericRating = $model->getNumericRating();
            $ratingCategory = $model->getRatingCategory();
            $moderation = $model->runPreliminaryModeration();

            $previousRatings = Review::find()
                ->where(['post_id' => $post->id])
                ->select('rating')
                ->column();

            $averageRating = $model->calculateAverageRating($previousRatings);
            $createdAt = date('Y-m-d H:i:s');

            $review = new Review();
            $review->post_id = (int)$post->id;
            $review->author_name = $model->authorName;
            $review->rating = $numericRating;
            $review->content = $model->content;
            $review->moderation_status = $moderation['status'];
            $review->created_at = $createdAt;

            if ($review->save()) {
                $ratingsCount = count($previousRatings) + 1;
                $formattedCreatedAt = Yii::$app->formatter->asDatetime($createdAt, 'php:d.m.Y H:i:s');

                Yii::$app->session->setFlash('success', 'Відгук успішно збережено у базі даних та опрацьовано.');

                return $this->render('comment-result', [
                    'post' => $post,
                    'authorName' => $review->author_name,
                    'rating' => (int)$review->rating,
                    'stars' => $model->getStarsString(),
                    'ratingCategory' => $ratingCategory,
                    'averageRating' => $averageRating,
                    'ratingsCount' => $ratingsCount,
                    'content' => $review->content,
                    'moderation' => $moderation,
                    'createdAt' => $formattedCreatedAt,
                ]);
            }

            foreach ($review->getFirstErrors() as $errorMessage) {
                $model->addError('content', $errorMessage);
            }
        }

        return $this->render('comment', [
            'post' => $post,
            'model' => $model,
        ]);
    }

    /**
     * Adds a comment or reply to an existing comment on a post.
     *
     * @param int $postId
     * @return Response
     * @throws NotFoundHttpException
     */
    public function actionAddComment($postId)
    {
        $post = Post::findOne(['id' => (int)$postId]);
        if ($post === null) {
            throw new NotFoundHttpException('Публікацію не знайдено.');
        }

        $comment = new Comment();
        $comment->post_id = (int)$post->id;

        if (!Yii::$app->user->isGuest && Yii::$app->user->identity) {
            $comment->user_id = (int)Yii::$app->user->id;
            if (empty($comment->author_name)) {
                $comment->author_name = (string)Yii::$app->user->identity->username;
            }
        }

        if ($this->request->isPost && $comment->load($this->request->post())) {
            $parentId = $comment->parent_id ? (int)$comment->parent_id : null;
            if ($parentId !== null) {
                $parentExists = Comment::find()
                    ->where(['id' => $parentId, 'post_id' => $post->id])
                    ->exists();
                if (!$parentExists) {
                    $comment->addError('parent_id', 'Батьківський коментар не знайдено для цієї публікації.');
                }
            }
            $comment->parent_id = $parentId;
            $comment->created_at = date('Y-m-d H:i:s');

            if (!$comment->hasErrors() && $comment->save()) {
                Yii::$app->session->setFlash(
                    'success',
                    $parentId !== null ? 'Відповідь на коментар додано.' : 'Коментар додано.'
                );
            } else {
                $errors = implode(' ', $comment->getFirstErrors());
                Yii::$app->session->setFlash('error', $errors ?: 'Не вдалося зберегти коментар.');
            }
        }

        return $this->redirect(['post/view', 'id' => $post->id, '#' => 'comments-section']);
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    /**
     * User registration action.
     *
     * @return Response|string
     */
    public function actionSignup()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new SignupForm();

        if ($model->load(Yii::$app->request->post()) && ($user = $model->signup())) {
            if (Yii::$app->user->login($user)) {
                Yii::$app->session->setFlash('success', 'Реєстрацію успішно завершено! Ласкаво просимо до блогу про мобільні телефони.');
                return $this->goHome();
            }
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }
}