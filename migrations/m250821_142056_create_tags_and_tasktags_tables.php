<?php

use yii\db\Migration;

class m250821_142056_create_tags_and_tasktags_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

     // Tag table
        $this->createTable('{{%tags}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(100)->notNull()->unique(),
        ]);

        // Junction table task_tag
        $this->createTable('{{%task_tags}}', [
            'task_id' => $this->integer()->notNull(),
            'tag_id' => $this->integer()->notNull(),
        ]);

        $this->addPrimaryKey('pk_task_tag', '{{%task_tags}}', ['task_id', 'tag_id']);

        $this->addForeignKey('fk_tasktag_task', '{{%task_tags}}', 'task_id', '{{%tasks}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_tasktag_tag', '{{%task_tags}}', 'tag_id', '{{%tags}}', 'id', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropTable('{{%task_tags}}');
        $this->dropTable('{{%tags}}');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250821_142056_create_tag_and_tasktag_tables cannot be reverted.\n";

        return false;
    }
    */
}
