<?php

namespace app\models;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

use yii\db\ActiveQuery;
use app\models\TaskLog;


/**
 * This is the model class for table "task".
 *
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string $status
 * @property string $priority
 * @property string|null $due_date
 * @property int $created_at
 * @property int $updated_at
 */
class Task extends \yii\db\ActiveRecord
{

    /**
     * ENUM field values
     */
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';

     /** @var int[] tag IDs supplied from UI/API */
    public $tagIds;

    /** @var string[] tag names supplied from UI/API */
    public array $tagNames = [];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%tasks}}';
    }


    public function setTagIds($value): void
    {
        // If blank or null, assign empty array
        $this->tagIds = is_array($value) ? $value : [];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title'], 'required'],
            [['title'], 'string', 'min' => 5,'max' => 255],
            [['description'], 'string'],
            ['status', 'in', 'range' => array_keys(self::optsStatus())],
            ['priority', 'in', 'range' => array_keys(self::optsPriority())],
            [['due_date'], 'date', 'format' => 'php:Y-m-d'],
            [['created_at', 'updated_at', 'deleted_at'], 'integer'],
            [['status'], 'default', 'value' => self::STATUS_PENDING],
            [['priority'], 'default', 'value' => self::PRIORITY_MEDIUM],  
            //['tagIds', 'each', 'rule' => ['safe']],
            ['tagIds', 'validateTagLength'],
            [
                ['tagIds'],
                'validateTagIds',
            ],
        ];
    }
    public function validateTagLength($attribute, $params)
    {
        if (!empty($this->$attribute)) {
            foreach ($this->$attribute as $tag) {
                if (is_string($tag) && mb_strlen($tag) > 30) {
                    $this->addError($attribute, "Tag '{$tag}' is too long (max 30 chars).");
                }
            }
        }
    }

    public function validateTagIds($attribute, $params)
    {
        if (!empty($this->tagIds)) {
            // Separate IDs vs strings
            $ids = array_filter($this->tagIds, 'is_int');
            $invalid = [];

            if (!empty($ids)) {
                $validIds = Tag::find()
                    ->select('id')
                    ->where(['id' => $ids])
                    ->column();

                $invalid = array_diff($ids, $validIds);
            }

            // Only mark error if invalid **IDs** exist
            if (!empty($invalid)) {
                $this->addError(
                    $attribute,
                    'Invalid tag IDs: ' . implode(', ', $invalid)
                );
            }
        }
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                'value' => function () {
                    return time(); // set UNIX timestamp
                },
            ],
        ];
    }

    public function getTaskTags()
    {
        return $this->hasMany(TaskTag::class, ['task_id' => 'id']);
    }

    public function getTags()
    {
        return $this->hasMany(Tag::class, ['id' => 'tag_id'])
            ->viaTable('{{%task_tags}}', ['task_id' => 'id']);
    }

    public function softDelete()
    {
        $this->deleted_at = time();
        return $this->save(false, ['deleted_at']);
        
    }

    public function beforeValidate()
    {
        if (is_array($this->tagIds)) {
            // Remove duplicates, reindex
            $this->tagIds = array_values(array_unique($this->tagIds, SORT_REGULAR));
        }
        return parent::beforeValidate();
    }


     public function afterFind(): void
    {
        parent::afterFind();
        $this->tagIds   = ArrayHelper::getColumn($this->tags, 'id');
        $this->tagNames = ArrayHelper::getColumn($this->tags, 'name');
    }

    public function afterSave($insert, $changedAttributes): void
    {
        parent::afterSave($insert, $changedAttributes);
        if ($insert) {
            // New record created → full snapshot
            $this->logChange('create');
        } elseif (array_key_exists('deleted_at', $changedAttributes) && $changedAttributes['deleted_at'] === null   && $this->deleted_at !== null) {
            // Soft deleted → final snapshot
            $this->logChange('delete');
        } else {
            // Normal update → store old/new diff
            $this->logChange('update', $changedAttributes);
        }
    }

    protected function logChange(string $action, array $changedAttributes = [])
    {
        if ($action === 'update') {
            $data = [];
            foreach ($changedAttributes as $attribute => $oldValue) {
                $data[$attribute] = [
                    'old' => $oldValue,
                    'new' => $this->$attribute, // current value after save
                ];
            }
        } elseif ($action === 'create') {
            $data = $this->attributes; // full snapshot of record
        } elseif ($action === 'delete') {
            $data = $this->attributes; // final snapshot before deletion
        }
        $log = new TaskLog();
        $log->task_id = $this->id;
        $log->action = $action;
        $log->changed_data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $log->created_at = time();
        $log->save(false);
    }

    /**
     * Exclude soft-deleted records by default
     */
    public static function find(): ActiveQuery
    {
        return parent::find()->alias('t')->andWhere(['t.deleted_at' => null]);
    }

    /**
     * Restore soft deleted record
     */
    public function restore(): bool
    {
        return (bool)$this->updateAttributes([
            'deleted_at' => null,
        ]);
    }

    /**
     * Scope to include deleted records if needed
     */
    public static function findWithDeleted(): ActiveQuery
    {
        return parent::find()->alias('t');
    }

    public function fields(): array
    {
        $fields = parent::fields();
        $fields['tags'] = function ($model) {
            return ArrayHelper::getColumn($model->tags, 'name');
        };
        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'description' => 'Description',
            'status' => 'Status',
            'priority' => 'Priority',
            'due_date' => 'Due Date',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'deleted_at'  => 'Deleted At',
            'tagIds'      => 'Tags',
            'tagNames'    => 'Tags',
        ];
    }


    /**
     * column status ENUM value labels
     * @return string[]
     */
    public static function optsStatus()
    {
        return [
            self::STATUS_PENDING => 'pending',
            self::STATUS_IN_PROGRESS => 'in_progress',
            self::STATUS_COMPLETED => 'completed',
        ];
    }

    /**
     * column priority ENUM value labels
     * @return string[]
     */
    public static function optsPriority()
    {
        return [
            self::PRIORITY_LOW => 'low',
            self::PRIORITY_MEDIUM => 'medium',
            self::PRIORITY_HIGH => 'high',
        ];
    }

    /**
     * @return string
     */
    public function displayStatus()
    {
        return self::optsStatus()[$this->status];
    }

    /**
     * @return bool
     */
    public function isStatusPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function setStatusToPending()
    {
        $this->status = self::STATUS_PENDING;
    }

    /**
     * @return bool
     */
    public function isStatusInprogress()
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function setStatusToInprogress()
    {
        $this->status = self::STATUS_IN_PROGRESS;
    }

    /**
     * @return bool
     */
    public function isStatusCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function setStatusToCompleted()
    {
        $this->status = self::STATUS_COMPLETED;
    }

    /**
     * @return string
     */
    public function displayPriority()
    {
        return self::optsPriority()[$this->priority];
    }

    /**
     * @return bool
     */
    public function isPriorityLow()
    {
        return $this->priority === self::PRIORITY_LOW;
    }

    public function setPriorityToLow()
    {
        $this->priority = self::PRIORITY_LOW;
    }

    /**
     * @return bool
     */
    public function isPriorityMedium()
    {
        return $this->priority === self::PRIORITY_MEDIUM;
    }

    public function setPriorityToMedium()
    {
        $this->priority = self::PRIORITY_MEDIUM;
    }

    /**
     * @return bool
     */
    public function isPriorityHigh()
    {
        return $this->priority === self::PRIORITY_HIGH;
    }

    public function setPriorityToHigh()
    {
        $this->priority = self::PRIORITY_HIGH;
    }
}
