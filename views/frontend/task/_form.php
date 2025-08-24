<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Tag;
use kartik\select2\Select2;
/** @var yii\web\View $this */
/** @var app\models\Task $model */
/** @var yii\widgets\ActiveForm $form */

//$allTags = ArrayHelper::map(Tag::find()->orderBy(['name' => SORT_ASC])->all(), 'id', 'name');

?>

<div class="task-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'description')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'status')->dropDownList([ 'pending' => 'Pending', 'in_progress' => 'In progress', 'completed' => 'Completed', ], ['prompt' => '']) ?>

    <?= $form->field($model, 'priority')->dropDownList([ 'low' => 'Low', 'medium' => 'Medium', 'high' => 'High', ], ['prompt' => '']) ?>

   <!-- Multi-select by IDs -->
    <?= $form->field($model, 'tagIds')->widget(Select2::class, [
        'data' => $allTags,
        'options' => [
            'placeholder' => 'Select or type tags...',
            'multiple' => true,
        ],
        'pluginOptions' => [
            'tags' => true,                // allow creating new tags
            'tokenSeparators' => [',', ' '], // typing comma/space creates new tag
            'maximumInputLength' => 30,    // optional: limit length
        ],
    ]); ?>

    <?= $form->field($model, 'due_date')->input('date') ?>


    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
