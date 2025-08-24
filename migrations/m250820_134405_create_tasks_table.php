<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%tasks}}`.
 */
class m250820_134405_create_tasks_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $statusColumn = $this->db->driverName === 'mysql'
            ? "ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending'"
            : $this->string()->notNull()->defaultValue('pending');

        $priorityColumn = $this->db->driverName === 'mysql'
            ? "ENUM('low','medium','high') NOT NULL DEFAULT 'medium'"
            : $this->string()->notNull()->defaultValue('medium');

        $this->createTable('{{%tasks}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'description' => $this->text()->null(),
            'status' => $statusColumn,
            'priority' => $priorityColumn,
            'due_date' => $this->date()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'deleted_at'=> $this->integer()->null(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%tasks}}');
    }
}
