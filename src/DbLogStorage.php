<?php

namespace Brezgalov\TablesLogs;

use yii\base\Component;
use yii\db\Connection;

class DbLogStorage extends Component implements ILogStorage
{
    /**
     * @var Connection
     */
    public $db;

    /**
     * DbLogStorage constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        if (empty($this->db)) {
            $this->db = $this->getConnection();
        }
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->db ?: \Yii::$app->db;
    }

    /**
     * @param string $tableName
     * @return string
     */
    public function quoteTableName(string $tableName)
    {
        return $this->getConnection()->quoteTableName($tableName);
    }
    
    /**
     * @return string
     */
    public function getLogsTableName()
    {
        return $this->quoteTableName('{{%tables_logs}}');
    }

    /**
     * @return string
     */
    public function getLogFieldsTableName()
    {
        return $this->quoteTableName('{{%tables_log_fields}}');
    }

    /**
     * @return string
     */
    public function getPKField()
    {
        return 'id';
    }

    /**
     * @param TableLogDto $logDto
     * @return bool|int
     * @throws \yii\base\NotSupportedException
     */
    public function storeLog(TableLogDto $logDto)
    {
        $logDto->createdAt = date('Y-m-d H:i:s');
        
        $data = $logDto->toArray();

        $storeRes = $this->getConnection()->getSchema()->insert(
            $this->getLogsTableName(),
            $logDto->toArray()
        );

        $id = $storeRes[$this->getPKField()] ?? false;
        if ($id) {
            $logDto->id = $id;
        }

        return $id;
    }

    /**
     * @param TableLogFieldDto $logFieldDto
     * @return bool|int
     * @throws \yii\base\NotSupportedException
     */
    public function storeLogFields(TableLogFieldDto $logFieldDto)
    {
        $data = $logFieldDto->toArray();

        $storeRes = $this->getConnection()->getSchema()->insert(
            $this->getLogFieldsTableName(),
            $logFieldDto->toArray()
        );

        $id = $storeRes[$this->getPKField()] ?? false;
        if ($id) {
            $logFieldDto->id = $id;
        }

        return $id;
    }
}