<?php

namespace app\controllers;

use app\models\Category;
use app\models\Comment;
use app\models\Post;
use app\models\PostSearch;
use app\models\Review;
use app\models\Tag;
use app\models\User;
use Throwable;
use Yii;
use yii\db\Query;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

class PostController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['index', 'create', 'update', 'delete', 'stats'],
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => static function () {
                            $identity = Yii::$app->user->identity;
                            if ($identity === null) {
                                return false;
                            }

                            $role = $identity->role ?? null;
                            $username = $identity->username ?? null;

                            return in_array($role, ['admin', 'author'], true)
                                || $username === 'admin_author';
                        },
                    ],
                ],
                'denyCallback' => static function () {
                    if (Yii::$app->user->isGuest) {
                        return Yii::$app->user->loginRequired();
                    }

                    throw new ForbiddenHttpException('Доступ до панелі адміністрування дозволено лише автору/адміністратору.');
                },
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel = new PostSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        $statsRows = Review::find()
            ->select([
                'post_id',
                'reviews_count' => 'COUNT(*)',
                'avg_rating' => 'ROUND(AVG(rating), 2)',
                'pending_count' => "SUM(CASE WHEN moderation_status = 'pending_review' THEN 1 ELSE 0 END)",
            ])
            ->groupBy('post_id')
            ->asArray()
            ->all();

        $reviewStatsByPost = [];
        foreach ($statsRows as $row) {
            $avg = (float)$row['avg_rating'];
            if ($avg <= 0) {
                $category = 'немає оцінок';
            } elseif ($avg < 2.5) {
                $category = 'низька';
            } elseif ($avg < 3.5) {
                $category = 'середня';
            } else {
                $category = 'висока';
            }

            $reviewStatsByPost[(int)$row['post_id']] = [
                'count' => (int)$row['reviews_count'],
                'avg' => $avg,
                'category' => $category,
                'pending' => (int)$row['pending_count'],
            ];
        }

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'reviewStatsByPost' => $reviewStatsByPost,
        ]);
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);

        $reviews = Review::find()
            ->where(['post_id' => $model->id])
            ->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC])
            ->all();

        $ratings = array_map(static fn(Review $r) => (int)$r->rating, $reviews);
        $ratingsCount = count($ratings);
        $averageRating = $ratingsCount > 0 ? round(array_sum($ratings) / $ratingsCount, 2) : 0.0;

        if ($ratingsCount === 0) {
            $averageCategory = 'немає оцінок';
        } elseif ($averageRating < 2.5) {
            $averageCategory = 'низька';
        } elseif ($averageRating < 3.5) {
            $averageCategory = 'середня';
        } else {
            $averageCategory = 'висока';
        }

        $filledStars = $ratingsCount > 0 ? max(1, min(5, (int)round($averageRating))) : 0;
        $averageStars = $ratingsCount > 0
            ? str_repeat('★', $filledStars) . str_repeat('☆', 5 - $filledStars)
            : '☆☆☆☆☆';

        $pendingCount = count(array_filter(
            $reviews,
            static fn(Review $r) => $r->moderation_status === Review::STATUS_PENDING_REVIEW
        ));

        $allComments = Comment::find()
            ->where(['post_id' => $model->id])
            ->orderBy(['created_at' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        $rootComments = [];
        $repliesByParent = [];
        foreach ($allComments as $item) {
            if ($item->parent_id === null) {
                $rootComments[] = $item;
            } else {
                $repliesByParent[(int)$item->parent_id][] = $item;
            }
        }

        return $this->render('view', [
            'model' => $model,
            'reviews' => $reviews,
            'reviewStats' => [
                'count' => $ratingsCount,
                'average' => $averageRating,
                'stars' => $averageStars,
                'category' => $averageCategory,
                'pendingCount' => $pendingCount,
            ],
            'rootComments' => $rootComments,
            'repliesByParent' => $repliesByParent,
            'newCommentForm' => new Comment(),
        ]);
    }

    public function actionStats()
    {
        $posts = Post::find()
            ->with(['category', 'reviews'])
            ->orderBy(['published_date' => SORT_DESC, 'id' => SORT_DESC])
            ->all();

        $postStatsList = [];

        foreach ($posts as $post) {
            /** @var Review[] $reviews */
            $reviews = $post->reviews;
            $count = count($reviews);

            $distribution = [
                1 => 0,
                2 => 0,
                3 => 0,
                4 => 0,
                5 => 0,
            ];

            if ($count > 0) {
                $ratings = [];
                foreach ($reviews as $review) {
                    $r = (int)$review->rating;
                    if ($r >= 1 && $r <= 5) {
                        $distribution[$r]++;
                        $ratings[] = $r;
                    }
                }

                $count = count($ratings);
                $avg = $count > 0 ? round(array_sum($ratings) / $count, 2) : null;
                $min = $count > 0 ? min($ratings) : null;
                $max = $count > 0 ? max($ratings) : null;
            } else {
                $avg = null;
                $min = null;
                $max = null;
            }

            if ($avg === null) {
                $category = 'немає оцінок';
            } elseif ($avg < 2.5) {
                $category = 'низька';
            } elseif ($avg < 3.5) {
                $category = 'середня';
            } else {
                $category = 'висока';
            }

            $postStatsList[] = [
                'post' => $post,
                'count' => $count,
                'avg' => $avg,
                'min' => $min,
                'max' => $max,
                'category' => $category,
                'distribution' => $distribution,
            ];
        }

        $rankingList = $postStatsList;
        usort($rankingList, static function (array $a, array $b): int {
            $aHas = $a['avg'] !== null;
            $bHas = $b['avg'] !== null;

            if ($aHas && !$bHas) {
                return -1;
            }
            if (!$aHas && $bHas) {
                return 1;
            }
            if (!$aHas && !$bHas) {
                return (int)$b['post']->id <=> (int)$a['post']->id;
            }

            $avgCmp = (float)$b['avg'] <=> (float)$a['avg'];
            if ($avgCmp !== 0) {
                return $avgCmp;
            }

            return (int)$b['count'] <=> (int)$a['count'];
        });

        return $this->render('stats', [
            'postStatsList' => $postStatsList,
            'rankingList' => $rankingList,
        ]);
    }

    public function actionCreate()
    {
        $model = new Post();
        $model->published_date = date('Y-m-d');

        if ($this->request->isPost) {
            $model->load($this->request->post());
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');

            $authorId = !Yii::$app->user->isGuest
                ? (int)Yii::$app->user->id
                : (int)(User::find()->select('id')->scalar() ?: 1);
            $model->author_id = $authorId;

            $transaction = Yii::$app->db->beginTransaction();

            try {
                if (!$model->validate()) {
                    $transaction->rollBack();
                    return $this->render('create', [
                        'model' => $model,
                        'categories' => $this->getCategoryList(),
                        'tags' => $this->getTagList(),
                    ]);
                }

                if (!$model->uploadImage()) {
                    $transaction->rollBack();
                    return $this->render('create', [
                        'model' => $model,
                        'categories' => $this->getCategoryList(),
                        'tags' => $this->getTagList(),
                    ]);
                }

                if (!$model->save(false)) {
                    $transaction->rollBack();
                    $model->addError('title', 'Не вдалося зберегти публікацію у базі даних.');
                    return $this->render('create', [
                        'model' => $model,
                        'categories' => $this->getCategoryList(),
                        'tags' => $this->getTagList(),
                    ]);
                }

                $this->syncPostTags($model);
                $transaction->commit();

                Yii::$app->session->setFlash('success', 'Публікацію успішно створено та додано до блогу.');
                return $this->redirect(['index']);
            } catch (Throwable $e) {
                $transaction->rollBack();
                Yii::error($e->getMessage(), __METHOD__);
                $model->addError('content', 'Сталася помилка під час збереження публікації: ' . $e->getMessage());
            }
        }

        return $this->render('create', [
            'model' => $model,
            'categories' => $this->getCategoryList(),
            'tags' => $this->getTagList(),
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $model->tagIds = array_map(static fn($tag) => (int)$tag->id, $model->tags);
        $oldImagePath = $model->image_path;

        if ($this->request->isPost) {
            $model->load($this->request->post());
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');

            $transaction = Yii::$app->db->beginTransaction();

            try {
                if (!$model->validate()) {
                    $transaction->rollBack();
                    return $this->render('update', [
                        'model' => $model,
                        'categories' => $this->getCategoryList(),
                        'tags' => $this->getTagList(),
                    ]);
                }

                if ($model->imageFile instanceof UploadedFile) {
                    if (!$model->uploadImage()) {
                        $transaction->rollBack();
                        return $this->render('update', [
                            'model' => $model,
                            'categories' => $this->getCategoryList(),
                            'tags' => $this->getTagList(),
                        ]);
                    }
                } else {
                    $model->image_path = $oldImagePath;
                }

                if (!$model->save(false)) {
                    $transaction->rollBack();
                    return $this->render('update', [
                        'model' => $model,
                        'categories' => $this->getCategoryList(),
                        'tags' => $this->getTagList(),
                    ]);
                }

                $this->syncPostTags($model);
                $transaction->commit();

                if ($model->imageFile instanceof UploadedFile && $oldImagePath && $oldImagePath !== $model->image_path) {
                    $oldFullPath = Yii::getAlias('@webroot/' . ltrim($oldImagePath, '/\\'));
                    if (is_file($oldFullPath)) {
                        @unlink($oldFullPath);
                    }
                }

                Yii::$app->session->setFlash('success', 'Публікацію успішно оновлено.');
                return $this->redirect(['index']);
            } catch (Throwable $e) {
                $transaction->rollBack();
                Yii::error($e->getMessage(), __METHOD__);
                $model->addError('content', 'Помилка під час оновлення публікації: ' . $e->getMessage());
            }
        }

        return $this->render('update', [
            'model' => $model,
            'categories' => $this->getCategoryList(),
            'tags' => $this->getTagList(),
        ]);
    }

    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $imagePath = $model->image_path;

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $model->delete();
            $transaction->commit();

            if ($imagePath) {
                $fullPath = Yii::getAlias('@webroot/' . ltrim($imagePath, '/\\'));
                if (is_file($fullPath)) {
                    @unlink($fullPath);
                }
            }

            Yii::$app->session->setFlash('success', 'Публікацію видалено.');
        } catch (Throwable $e) {
            $transaction->rollBack();
            Yii::error($e->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('error', 'Не вдалося видалити публікацію.');
        }

        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        if (($model = Post::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Запитувану публікацію не знайдено.');
    }

    protected function getCategoryList()
    {
        return Category::find()
            ->select(['name', 'id'])
            ->orderBy(['name' => SORT_ASC])
            ->indexBy('id')
            ->column();
    }

    protected function getTagList()
    {
        return Tag::find()
            ->select(['name', 'id'])
            ->orderBy(['name' => SORT_ASC])
            ->indexBy('id')
            ->column();
    }

    protected function syncPostTags(Post $post)
    {
        $db = Yii::$app->db;
        $db->createCommand()->delete('{{%post_tags}}', ['post_id' => $post->id])->execute();

        if (!is_array($post->tagIds) || empty($post->tagIds)) {
            return;
        }

        $validTagIds = Tag::find()
            ->select('id')
            ->where(['id' => array_map('intval', $post->tagIds)])
            ->column();

        if (empty($validTagIds)) {
            return;
        }

        $rows = [];
        foreach (array_unique($validTagIds) as $tagId) {
            $rows[] = [(int)$post->id, (int)$tagId];
        }

        (new Query())->createCommand()->batchInsert('{{%post_tags}}', ['post_id', 'tag_id'], $rows)->execute();
    }
}