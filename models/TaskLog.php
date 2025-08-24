<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "task_log".
 *
 * @property int $id
 * @property int $task_id
 * @property string $action
 * @property string|null $changed_data
 * @property int $created_at
 *
 * @property Tasks $task
 */
class TaskLog extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'task_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['changed_data'], 'default', 'value' => null],
            [['task_id', 'action', 'created_at'], 'required'],
            [['task_id', 'created_at'], 'integer'],
            [['changed_data'], 'string'],
            [['action'], 'string', 'max' => 50],
            [['task_id'], 'exist', 'skipOnError' => true, 'targetClass' => Tasks::class, 'targetAttribute' => ['task_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'task_id' => 'Task ID',
            'action' => 'Action',
            'changed_data' => 'Changed Data',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Gets query for [[Task]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTask()
    {
        return $this->hasOne(Tasks::class, ['id' => 'task_id']);
    }

}
