<?php

namespace app\controllers\api;

use yii\rest\Controller;
use yii\web\Response;
use Yii;
use app\components\ApiResponse;
use app\services\TaskService;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use app\models\Task;
use app\models\Tag;

/**
 * TaskController
 *
 * REST API controller for managing tasks.
 * Provides CRUD operations, soft delete, restore, status toggle, 
 * and tag list endpoints.
 *
 * All responses are standardized using ApiResponse helper.
 */
class TaskController extends Controller
{
    public $modelClass = 'app\models\Task';
    /** @var TaskService Business logic layer injected via DI */
    private TaskService $taskService;

    /**
     * Constructor with TaskService dependency injection
     */
    public function __construct($id, $module, TaskService $taskService, $config = [])
    {
        $this->taskService = $taskService;
        parent::__construct($id, $module, $config);
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        // Simple Bearer Token Authentication
        $behaviors['authenticator'] = [
            'class' => \app\components\SimpleBearerAuth::class,
        ];

        return $behaviors;
    }

    // Optional: override actions for custom filtering
    public function actions()
    {
        $actions = parent::actions();

        // Example: disable "delete" via API
        // unset($actions['delete']);
        
        return $actions;
    }

    /**
     * GET /api/task
     * List all tasks with pagination
     */
    public function actionIndex()
    {
        try {
            $data = $this->taskService->listTasks(Yii::$app->request->queryParams??[]);
            $response = ['items' => $data['dataProvider']->getModels(),
                             'meta' => [
                                'total' => $data['dataProvider']->getTotalCount(),
                                'page' => (!empty($data['dataProvider']->pagination->page) ? $data['dataProvider']->pagination->page + 1 : 1),
                                'per_page' => (!empty($data['dataProvider']->pagination->pageSize) ? $data['dataProvider']->pagination->pageSize : 10),
                            ]
                        ];

            return ApiResponse::success('Tasks fetched successfully',200,$response);
        } catch (\Throwable $e) {
            return ApiResponse::error('Internal Server Error', 500, [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * GET /api/task/{id}
     * View details of a single task
     */

    public function actionView($id)
    {
        try {
            $task = $this->taskService->getTask($id);
            return ApiResponse::success('Task details fetched successfully',200,$task->toArray());
        } catch (\yii\web\NotFoundHttpException $e) {
            return ApiResponse::error('Task not found', 404,['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            // fallback for unexpected errors
            Yii::error($e->getMessage(), __METHOD__);
            return ApiResponse::error('Internal Server Error', 500,['error' => $e->getMessage()]);
        }
    }

    /**
     * POST /api/task
     * Create a new task
     */
    public function actionCreate()
    {   
        try {
            $request = Yii::$app->request->getBodyParams();
            $task = new Task();
            if ($this->taskService->createTask($task, $request,true)) {
                $data = $task->toArray();
                $data['tags'] = $task->tags;
                return ApiResponse::success(
                    'Task created successfully',
                    201, $data
                );
            }

            // If validation failed
            return ApiResponse::error(
                'Validation failed',
                422,
                $task->getErrors()
            );
        }
        catch (\Throwable $e) {
            // fallback for unexpected errors
            Yii::error($e->getMessage(), __METHOD__);
            return ApiResponse::error('Internal Server Error', 500,['error' => $e->getMessage()]);
        }
    }

    /**
     * Patch /api/task/{id}
     * Update an existing task
     */
    public function actionUpdate($id)
    {
        try {
            $task = $this->taskService->getTask($id); // fetch task or 404
            $request = Yii::$app->request->getBodyParams();

            if ($this->taskService->updateTask($task, $request, true)) {
                $data = $task->toArray();
                $data['tags'] = $task->tags; // comma separated
                return ApiResponse::success('Task updated successfully', 200, $data);
            }

            return ApiResponse::error(
                'Validation failed',
                422,
                $task->getErrors()
            );
        } catch (\yii\web\NotFoundHttpException $e) {
            return ApiResponse::error('Task not found', 404,['error' => $e->getMessage()]);
        }catch (\Throwable $e) {
            // fallback for unexpected errors
            Yii::error($e->getMessage(), __METHOD__);
            return ApiResponse::error('Internal Server Error', 500,['error' => $e->getMessage()]);
        }
    }

   /**
     * DELETE /api/task/{id}
     * Soft delete a task
     */
    public function actionDelete($id)
    {
        try {
            if ($this->taskService->softDeleteTask($id)) {
                return ApiResponse::success('Task deleted successfully', 200);
            }
            return ApiResponse::error('Failed to delete task', 500);
        } catch (NotFoundHttpException $e) {
            return ApiResponse::error('Task not found', 404, ['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), __METHOD__);
            return ApiResponse::error('Internal Server Error', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * PATCH /api/task/{id}/restore
     * Restore a soft-deleted task
     */
    public function actionRestore($id)
    {
        try {
            $task = $this->taskService->restoreTask($id);
            return ApiResponse::success('Task restored successfully', 200, $task->toArray());
        } catch (NotFoundHttpException $e) {
            return ApiResponse::error('Task not found', 404, ['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), __METHOD__);
            return ApiResponse::error('Internal Server Error', 500, ['error' => $e->getMessage()]);
        }
    }

   /**
     * PATCH /api/task/{id}/toggle-status
     * Cycle status: pending → in_progress → completed
     */
    public function actionToggleStatus($id)
    {
        try {
            $task = $this->taskService->getTask($id); // fetch task or throw 404

            if ($this->taskService->toggleStatus($task)) {
                $data = $task->toArray();
                $data['tags'] = $task->tagNames; // keep consistent response
                return ApiResponse::success('Task status updated successfully', 200, $data);
            }

            return ApiResponse::error('Failed to update status', 500);

        } catch (\yii\web\NotFoundHttpException $e) {
            return ApiResponse::error('Task not found', 404, ['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), __METHOD__);
            return ApiResponse::error('Internal Server Error', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/task/tag-list
     * Fetch all tags for dropdown / autocomplete
     */ 
    public function actionTagList()
    {
        $search = Yii::$app->request->get('search');
        $query = Tag::find();

        if (!empty($search)) {
            $query->andWhere(['like', 'name', $search]);
        }
        $tags = $query->orderBy(['name' => SORT_ASC])->all();
        return ApiResponse::success(
            'Tags fetched successfully',
            200,
            $tags
        );
    }
}