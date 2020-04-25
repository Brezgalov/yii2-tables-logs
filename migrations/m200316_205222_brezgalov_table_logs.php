<?php

use yii\db\Migration;

/**
 * Class m200316_205222_table_logs
 */
class m200316_205222_brezgalov_table_logs extends Migration
{
    public $table = '';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createLog();
        $this->createFields();
    }

    private function createLog()
    {
        $this->table = $this->db->tablePrefix . 'tables_logs';

        $this->createTable($this->table, [
            'id'                => $this->primaryKey(),
            'table'             => $this->string()->notNull(),
            'log_type'          => $this->string()->defaultValue('default'),
            'action'            => $this->string()->notNull(),
            'class_name'        => $this->string(),
            'record_id'         => $this->integer(),
            'user_id'           => $this->integer(),
            'user_ip'           => $this->string(),
            'user_agent'        => $this->string(),
            'referer'           => $this->string(),
            'controller_name'   => $this->string(),
            'action_name'       => $this->string(),
            'created_at'        => $this->dateTime(),
        ]);

        $this->createIndex(
            $this->table . '_IDX_log_type',
            $this->table,
            'log_type'
        );

        $this->createIndex(
            $this->table . '_IDX_record_id',
            $this->table,
            'record_id'
        );
        $this->createIndex(
            $this->table . '_IDX_action',
            $this->table,
            'action'
        );
        $this->createIndex(
            $this->table . '_IDX_table',
            $this->table,
            'table'
        );
        $this->createIndex(
            $this->table . '_IDX_class_name',
            $this->table,
            'class_name'
        );
    }

    private function createFields()
    {
        $this->table = $this->db->tablePrefix . 'tables_log_fields';

        $this->createTable($this->table, [
            'id'                => $this->primaryKey(),
            'log_id'            => $this->integer()->notNull(),
            'key'               => $this->string(),
            'value'             => $this->string(),
            'value_previous'    => $this->string(),
        ]);

        $this->createIndex(
            $this->table . '_IDX_key',
            $this->table,
            'key'
        );

        $this->createIndex(
            $this->table . '_IDX_log_id',
            $this->table,
            'log_id'
        );
        $this->addForeignKey(
            $this->table . '_FK_log_id',
            $this->table,
            'log_id',
            'tables_logs',
            'id',
            'cascade'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m200316_205222_table_logs cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200316_205222_table_logs cannot be reverted.\n";

        return false;
    }
    */
}
