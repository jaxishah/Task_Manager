<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%task_logs}}`.
 */
class m250823_103921_create_task_logs_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%task_log}}', [
            'id' => $this->primaryKey(),
            'task_id' => $this->integer()->notNull(),
            'action' => $this->string(50)->notNull(), // create, update, delete
            'changed_data' => $this->text()->null(), // JSON of changes
            'created_at' => $this->integer()->notNull(),
        ]);

        // Add foreign key
        $this->addForeignKey(
            'fk_task_log_task',
            '{{%task_log}}',
            'task_id',
            '{{%tasks}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_task_log_task', '{{%task_log}}');
        $this->dropTable('{{%task_log}}');
    }
}
