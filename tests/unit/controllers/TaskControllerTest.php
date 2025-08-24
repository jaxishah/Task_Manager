<?php
namespace tests\unit\controllers\api;

use app\controllers\api\TaskController;
use app\services\TaskService;
use yii\web\Request;
use yii\web\Response;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\web\HeaderCollection;
use yii\web\UnauthorizedHttpException;

class TaskControllerTest extends TestCase
{
    private TaskController $controller;
    private $taskService;

    protected function setUp(): void
    {
        parent::setUp();
        // Pass mock dependencies or real Yii::$app if already bootstrapped
        $module = \Yii::$app->controller->module ?? \Yii::$app->getModule('api'); // or null if no module
        $this->taskService = $this->createMock(TaskService::class);
        $this->controller = new \app\controllers\api\TaskController('task', $module, $this->taskService);

    }

    /** 1. Happy Path */
    public function testCreateTaskSuccess()
    {
        $this->taskService->method('createTask')->willReturn(true);

        $request = $this->createMock(Request::class);
        $request->method('getBodyParams')->willReturn([
            'title' => 'Valid Task',
            'status' => 'pending',
            'due_date' => '2025-08-30',
        ]);

        Yii::$app->set('request', $request);
        Yii::$app->set('response', new Response());

        $result = $this->controller->actionCreate();

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('Task created successfully', $result['message']);
        $this->assertArrayHasKey('data', $result);
    }

    /** 2. Missing required field */
    public function testCreateTaskMissingTitleFails()
    {
        $this->taskService->method('createTask')->willReturn(false);

        $request = $this->createMock(Request::class);
        $request->method('getBodyParams')->willReturn([]);

        Yii::$app->set('request', $request);
        Yii::$app->set('response', new Response());

        $result = $this->controller->actionCreate();

        $this->assertFalse($result['success']);
        $this->assertEquals('Validation failed', $result['message']);
    }

    /** 3. Invalid status */
    public function testCreateTaskInvalidStatusFails()
    {
        $this->taskService->method('createTask')->willReturn(false);

        $request = $this->createMock(Request::class);
        $request->method('getBodyParams')->willReturn([
            'title' => 'Task',
            'status' => 'invalid_status',
        ]);

        Yii::$app->set('request', $request);
        Yii::$app->set('response', new Response());

        $result = $this->controller->actionCreate();
        $this->assertFalse($result['success']);
    }

    /** 4. Invalid date */
    public function testCreateTaskInvalidDateFails()
    {
        $this->taskService->method('createTask')->willReturn(false);

        $request = $this->createMock(Request::class);
        $request->method('getBodyParams')->willReturn([
            'title' => 'Task',
            'due_date' => 'invalid-date',
        ]);

        Yii::$app->set('request', $request);
        Yii::$app->set('response', new Response());

        $result = $this->controller->actionCreate();
        $this->assertFalse($result['success']);
    }

    /** 5. Empty body */
    public function testCreateTaskEmptyBodyFails()
    {
        $this->taskService->method('createTask')->willReturn(false);

        $request = $this->createMock(Request::class);
        $request->method('getBodyParams')->willReturn([]);

        Yii::$app->set('request', $request);
        Yii::$app->set('response', new Response());

        $result = $this->controller->actionCreate();
        $this->assertFalse($result['success']);
    }

    /** 6. Extra unexpected fields (should still succeed) */
    public function testCreateTaskWithExtraFields()
    {
        $this->taskService->method('createTask')->willReturn(true);

        $request = $this->createMock(Request::class);
        $request->method('getBodyParams')->willReturn([
            'title' => 'Task',
            'random_field' => 'unexpected',
        ]);

        Yii::$app->set('request', $request);
        Yii::$app->set('response', new Response());

        $result = $this->controller->actionCreate();
        $this->assertTrue($result['success']);
    }


    /** 8. Internal error in service */
    public function testCreateTaskServiceThrowsException()
    {
        $this->taskService->method('createTask')->willThrowException(new \Exception('DB Error'));

        $request = $this->createMock(Request::class);
        $request->method('getBodyParams')->willReturn([
            'title' => 'New Task'
        ]);

        Yii::$app->set('request', $request);
        Yii::$app->set('response', new Response());

        $result = $this->controller->actionCreate();

        $this->assertFalse($result['success']);
        $this->assertEquals('Internal Server Error', $result['message']);
        $this->assertEquals(500, $result['status']);
        $this->assertEquals('DB Error', $result['errors']['error']); // your wrapped error
    }


    public function testIndexSuccess()
    {
        // Create real pagination object
        $pagination = new \yii\data\Pagination([
            'pageSize'   => 10,
            'page'       => 0,   // 0-based
            'totalCount' => 1,
        ]);

        // Mock DataProvider
        $mockDataProvider = $this->createMock(\yii\data\ActiveDataProvider::class);
        $mockDataProvider->method('getModels')->willReturn([['id' => 1, 'title' => 'Test Task']]);
        $mockDataProvider->method('getTotalCount')->willReturn(1);

        // IMPORTANT: force pagination property to return real object
        $mockDataProvider->pagination = $pagination;

        // Mock request
        $request = $this->createMock(\yii\web\Request::class);
        $request->method('getQueryParams')->willReturn([]);
        \Yii::$app->set('request', $request);
        \Yii::$app->set('response', new \yii\web\Response());

        // Mock service
        $this->taskService
            ->method('listTasks')
            ->willReturn(['dataProvider' => $mockDataProvider]);

        // Run
        $result = $this->controller->actionIndex();
        // Assertions
        $this->assertTrue($result['success']);
        $this->assertEquals('Tasks fetched successfully', $result['message']);
        $this->assertArrayHasKey('items', $result['data']);
        $this->assertEquals(1, $result['data']['meta']['page']); // 0+1
    }


    public function testIndexThrowsException()
    {
        $this->taskService
            ->method('listTasks')
            ->willThrowException(new \Exception('DB error'));

        Yii::$app->set('response', new \yii\web\Response());

        $result = $this->controller->actionIndex();
        $this->assertFalse($result['success']);
        $this->assertEquals('Internal Server Error', $result['message']);
        $this->assertEquals(500, $result['status']);
        $this->assertEquals('DB error', $result['errors']['error']);  //  check error details
    }


/** ---------------- VIEW ---------------- **/

    /** 1. View success */
    public function testViewTaskSuccess()
    {
        $mockTask = new \app\models\Task();
        $mockTask->id = 1;
        $mockTask->title = 'Test Task';

        $this->taskService
            ->method('getTask')
            ->with(1)
            ->willReturn($mockTask);

        Yii::$app->set('response', new Response());

        $result = $this->controller->actionView(1);

        $this->assertTrue($result['success']);
        $this->assertEquals('Task details fetched successfully', $result['message']);
        $this->assertEquals($mockTask->toArray(), $result['data']);
    }


    /** 2. View not found */
    public function testViewTaskNotFound()
    {
    $this->taskService
            ->method('getTask')
            ->willThrowException(new \yii\web\NotFoundHttpException('Task not found'));

        Yii::$app->set('response', new Response());

        $result = $this->controller->actionView(999);
        // Assert proper error response
        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals('Task not found', $result['message']);
        $this->assertEquals(404, $result['status']);
    }


// /** ---------------- UPDATE ---------------- **/

    // /** 1. Update success */
    public function testUpdateTaskSuccess()
    {
        $mockTask = new \app\models\Task(['id' => 1, 'title' => 'Old Task']);

        // Instead of assigning directly, mock getTags()
        $mockTask = $this->getMockBuilder(\app\models\Task::class)
            ->onlyMethods(['getTags'])
            ->setConstructorArgs([['id' => 1, 'title' => 'Old Task']])
            ->getMock();

        $mockTask->method('getTags')->willReturn(['tag1']);

        $this->taskService
            ->method('getTask')
            ->willReturn($mockTask);

        $this->taskService
            ->method('updateTask')
            ->willReturn(true);

        $request = $this->createMock(\yii\web\Request::class);
        $request->method('getBodyParams')->willReturn(['title' => 'Updated Task']);
        \Yii::$app->set('request', $request);
        \Yii::$app->set('response', new \yii\web\Response());

        $result = $this->controller->actionUpdate(1);

        $this->assertTrue($result['success']);
        $this->assertEquals('Task updated successfully', $result['message']);
        $this->assertArrayHasKey('data', $result);
    }


// /** 2. Update validation fail */
    public function testUpdateTaskValidationFails()
    {
        $mockTask = new \app\models\Task();

        $this->taskService
            ->method('getTask')
            ->willReturn($mockTask);

        $this->taskService
            ->method('updateTask')
            ->willReturn(false);

        $request = $this->createMock(Request::class);
        $request->method('getBodyParams')->willReturn([]);
        Yii::$app->set('request', $request);
        Yii::$app->set('response', new Response());

        $result = $this->controller->actionUpdate(1);

        $this->assertFalse($result['success']);
        $this->assertEquals('Validation failed', $result['message']);
    }


// /** ---------------- DELETE ---------------- **/

    // /** 1. Delete success */
    public function testDeleteTaskSuccess()
    {
        $this->taskService
            ->method('softDeleteTask')
            ->with(1)
            ->willReturn(true);

        Yii::$app->set('response', new Response());

        $result = $this->controller->actionDelete(1);

        $this->assertTrue($result['success']);
        $this->assertEquals('Task deleted successfully', $result['message']);
    }

    // /** 2. Delete not found */
    public function testDeleteTaskNotFound()
    {
        $this->taskService
            ->method('softDeleteTask')
            ->willThrowException(new \yii\web\NotFoundHttpException('Task not found'));

        Yii::$app->set('response', new Response());

        $result = $this->controller->actionDelete(999);

        $this->assertFalse($result['success']);
        $this->assertEquals('Task not found', $result['message']);
    }


    // /** ---------------- RESTORE ---------------- **/

    // /** 1. Restore success */
    public function testRestoreTaskSuccess()
    {
        $mockTask = new \app\models\Task();
        $mockTask->id = 1;
        $mockTask->title = 'Restored Task';

        $this->taskService
            ->method('restoreTask')
            ->with(1)
            ->willReturn($mockTask);

        Yii::$app->set('response', new \yii\web\Response());

        $result = $this->controller->actionRestore(1);

        $this->assertTrue($result['success']);
        $this->assertEquals('Task restored successfully', $result['message']);
        $this->assertEquals($mockTask->toArray(), $result['data']);  //  compare arrays
    }




    // /** 2. Restore not found */
    public function testRestoreTaskNotFound()
    {
        $this->taskService
            ->method('restoreTask')
            ->willThrowException(new \yii\web\NotFoundHttpException('Task not found'));

        Yii::$app->set('response', new Response());

        $result = $this->controller->actionRestore(999);

        $this->assertFalse($result['success']);
        $this->assertEquals('Task not found', $result['message']);
    }


}
