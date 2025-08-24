<?php

use app\models\Task;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var app\models\TaskSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Tasks';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="task-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Create Task', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'title',
            [
                'attribute' => 'status',
                'filter' => [
                    'pending' => 'Pending',
                    'in_progress' => 'In Progress',
                    'completed' => 'Completed',
                ],
                'value' => function ($model) {
                    return $model->status;
                }
            ],
            [
                'attribute' => 'priority', 
                'filter' => [
                    'low' => 'Low',
                    'medium' => 'Medium',
                    'high' => 'High',
                ],
                'value' => function ($model) {
                    return $model->priority ?: '(not set)'; // handle empty/null values
                },
            ],
            [
                'attribute' => 'due_date',
                //'format' => ['date', 'php:Y-m-d'], // format date nicely
                'value' => function ($model) {
                    return $model->due_date ?: '(not set)';
                },
            ],
            [
                'class' => ActionColumn::className(),
                'urlCreator' => function ($action, Task $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'id' => $model->id]);
                 }
            ],
        ],
    ]); ?>


</div>
