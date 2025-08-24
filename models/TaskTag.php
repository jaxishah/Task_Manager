<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "task_tag".
 *
 * @property int $task_id
 * @property int $tag_id
 *
 * @property Tag $tag
 * @property Task $task
 */
class TaskTag extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%task_tags}}';
    }

    /** Composite PK for clarity (AR supports this for basic ops) */
    public static function primaryKey(): array
    {
        return ['task_id', 'tag_id'];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['task_id', 'tag_id'], 'required'],
            [['task_id', 'tag_id'], 'integer'],
            [['task_id', 'tag_id'], 'unique', 'targetAttribute' => ['task_id', 'tag_id']],
            [['tag_id'], 'exist', 'skipOnError' => true, 'targetClass' => Tag::class, 'targetAttribute' => ['tag_id' => 'id']],
            [['task_id'], 'exist', 'skipOnError' => true, 'targetClass' => Task::class, 'targetAttribute' => ['task_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'task_id' => 'Task ID',
            'tag_id' => 'Tag ID',
        ];
    }

    /**
     * Gets query for [[Tag]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTag()
    {
        return $this->hasOne(Tag::class, ['id' => 'tag_id']);
    }

    /**
     * Gets query for [[Task]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTask()
    {
        return $this->hasOne(Task::class, ['id' => 'task_id']);
    }

}
