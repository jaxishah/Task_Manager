<?php

namespace app\services;

use app\models\Task;
use app\models\TaskTag;
use app\models\Tag;
use app\models\TaskSearch;
use yii\web\NotFoundHttpException;
use Yii;

class TaskService
{
    /**
     * Get list of tasks with search and pagination.
     *
     * @param array $queryParams
     * @return array
     */
    public function listTasks(array $queryParams): array
    {
        $searchModel = new TaskSearch();
        $dataProvider = $searchModel->search($queryParams);

        return [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ];
    }

    /**
     * Find task by ID.
     *
     * @param int $id
     * @return Task
     * @throws NotFoundHttpException
     */
    public function getTask(int $id): Task
    {
        $task = Task::findOne(['id' => $id]);
        
        if ($task === null) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
        $task->populateRelation('tags', $task->tags);
        
        return $task;
    }

    /**
     * Create a new task.
     *
     * @param Task $task
     * @param array $data
     * @return bool
     */
    public function createTask(Task $task, array $data, bool $isApi = false): bool
    {
        // load data ('' means no form name prefix for API JSON body)
        $formName = $isApi ? '' : null;
        $task->load($data, $formName);

        // validate first
        if (!$task->validate()) {
            return false; // validation errors remain in $task->errors
        }

        if ($task->save(false)) { 
            $this->syncTags($task, $task->tagIds);
            return true;
        }

        return false;
    }

    /**
     * Update an existing task.
     *
     * @param Task $task
     * @param array $data
     * @return bool
     */
    public function updateTask(Task $task, array $data, bool $isApi = false): bool
    {
       // return $task->load($data) && $task->save();
        // load data ('' means no form name prefix for API JSON body)
        $formName = $isApi ? '' : null;
        $task->load($data, $formName);

        // validate first
        if (!$task->validate()) {
            return false; // validation errors remain in $task->errors
        }

        if ($task->save(false)) { 
            $this->syncTags($task, $task->tagIds);
            return true;
        }

        return false;
    }

     /**
     * Sync tags with a task (unlink old, add new).
     */
    private function syncTags(Task $task,  $tagValues=[]): void
    {
        $tx = Yii::$app->db->beginTransaction();
        try {
            $task->unlinkAll('tags', true);
            if(!empty($tagValues)){
                foreach ((array)$tagValues as $value) {
                    $tag = null;

                    // Case 1: numeric ID
                    if (ctype_digit((string)$value)) {
                        $tag = Tag::findOne((int)$value);
                    }

                    // Case 2: new tag name
                    if ($tag === null && !ctype_digit((string)$value)) {
                        $tagName = trim((string)$value);
                        if ($tagName !== '') {
                            $tag = Tag::findOne(['name' => $tagName]);
                            if (!$tag) {
                                $tag = new Tag(['name' => $tagName]);
                                $tag->save(false);
                            }
                        }
                    }

                    if ($tag) {
                        $task->link('tags', $tag);
                    }
                }
            }

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }

   /**
     * Soft delete a task by ID.
     *
     * @param int $id
     * @return bool
     * @throws NotFoundHttpException
     */
    public function softDeleteTask(int $id): bool
    {
        $task = Task::findOne($id);
        if (!$task) {
            throw new NotFoundHttpException("Task not found");
        }
        return $task->softDelete();
    }

    /**
     * Restore a soft-deleted task.
     *
     * @param int $id
     * @return Task
     * @throws NotFoundHttpException
     */
    public function restoreTask(int $id): Task
    {
        $task = Task::findWithDeleted()->where(['id' => $id])->one();
        if (!$task) {
            throw new NotFoundHttpException("Task not found");
        }

        if (!$task->restore()) {
            throw new \RuntimeException("Failed to restore task");
        }

        return $task;
    }

    /**
     * Toggle task status (pending → in_progress → completed → pending).
     *
     * @param Task $task
     * @return bool
     */
    public function toggleStatus(Task $task): bool
    {
        $statusCycle = array_keys(Task::optsStatus());
        //['pending', 'in_progress', 'completed'];

        $currentIndex = array_search($task->status, $statusCycle, true);
        if ($currentIndex === false) {
            // default to pending if status invalid
            $task->status = Task::STATUS_PENDING;
        } else {
            $nextIndex = ($currentIndex + 1) % count($statusCycle);
            $task->status = $statusCycle[$nextIndex];
        }

        return $task->save(false, ['status']);
    }

}
