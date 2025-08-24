<?php

namespace app\controllers;

use app\models\Task;
use app\models\TaskSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\services\TaskService;
use Yii;
use yii\helpers\ArrayHelper;
use app\models\Tag;

/**
 * TaskController implements the CRUD actions for Task model.
 */
class TaskController extends Controller
{
    private TaskService $taskService;
    public $layout = '@app/views/frontend/layouts/main.php';
    public function __construct($id, $module, TaskService $taskService, $config = [])
    {
        $this->taskService = $taskService;
        parent::__construct($id, $module, $config);
    }

    public function getViewPath()
    {
        return \Yii::getAlias('@app/views/frontend/task');
    }
    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'verbs' => [
                    'class' => VerbFilter::className(),
                    'actions' => [
                        'delete' => ['POST'],
                    ],
                ],
            ]
        );
    }

    /**
     * Lists all Task models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $result = $this->taskService->listTasks(Yii::$app->request->queryParams);
        return $this->render('index', $result);

    }

    /**
     * Displays a single Task model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $result = $this->taskService->getTask($id);
        return $this->render('view', ['model' => $result]);
    }

    /**
     * Creates a new Task model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Task();
        if ($this->request->isPost) {
            if ($this->taskService->createTask($model, Yii::$app->request->post())) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
        } else {
            $model->loadDefaultValues();
        }
        $allTags = ArrayHelper::map(Tag::find()->orderBy(['name' => SORT_ASC])->all(), 'id', 'name');
        
        return $this->render('create', [
            'model' => $model, 'allTags' => $allTags,
            
        ]);
    }

    /**
     * Updates an existing Task model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

       if (Yii::$app->request->isPost &&
            $this->taskService->updateTask($model, Yii::$app->request->post(), false)) {
            return $this->redirect(['view', 'id' => $model->id]);
        }
        $allTags = ArrayHelper::map(Tag::find()->orderBy(['name' => SORT_ASC])->all(), 'id', 'name');
        return $this->render('update', [
            'model' => $model, 'allTags' => $allTags,
        ]);
    }

    /**
     * Deletes an existing Task model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->taskService->softDeleteTask($id);

        return $this->redirect(['index']);
    }

    /**
     * Finds the Task model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Task the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Task::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
