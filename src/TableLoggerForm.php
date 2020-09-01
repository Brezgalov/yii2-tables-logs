<?php

namespace Brezgalov\TablesLogs;

use yii\base\Model;
use yii\db\ActiveRecord;
use yii\helpers\Json;

class TableLoggerForm extends Model
{
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';

    /**
     * @var TablesLogs
     */
    protected $logTable;

    /**
     * @var TablesLogFields[]
     */
    protected $logTableFields = [];

    /**
     * @var array
     */
    public $fieldsToIgnore = [
        'id',
        'updated_at',
    ];

    /**
     * @var string
     */
    public $defaultLogType = TablesLogs::LOG_TYPE_DEFAULT;

    /**
     * @param array $params
     * @return TableLoggerForm
     */
    public static function createLog(array $params = [])
    {
        return new static($params);
    }

    /**
     * @param array $data
     * @return array
     */
    private function removeIgnoredFields(array $data)
    {
        foreach ($this->fieldsToIgnore as $field) {
            unset($data[$field]);
        }

        return $data;
    }

    /**
     * @param array $fields
     * @return $this
     */
    public function ignoreFields(array $fields)
    {
        $this->fieldsToIgnore = $fields;
        return $this;
    }

    /**
     * Дефолтный тип логов
     * @return string
     */
    public function getDefaultLogType()
    {
        return $this->defaultLogType;
    }

    /**
     * @return integer|null
     */
    public function getCurrentUserId()
    {
        return isset(\Yii::$app->user) ? \Yii::$app->user->id : null;
    }

    /**
     * return ActiveRecord model class name representing log record
     * @return string
     */
    public function getLogsTableClass()
    {
        return TablesLogs::class;
    }

    /**
     * return ActiveRecord model class name representing fields of log record
     * @return string
     */
    public function getLogFieldsTableClass()
    {
        return TablesLogFields::class;
    }

    /**
     * @param ActiveRecord $record
     * @param null $logType
     * @param null $primaryKeyField
     * @return $this
     */
    public function fromRecord(ActiveRecord $record, $logType = null, $primaryKeyField = null)
    {
        if (empty($primaryKeyField)) {
            $pKeys = array_keys($record->getPrimaryKey(true));
            $primaryKeyField = array_shift($pKeys);
        }

        $this->prepareLogTable(
            $record::tableName(),
            $record::className(),
            @$record->{$primaryKeyField} ?: null,
            $logType
        );

        $this->prepareLogFields($record->toArray());

        return $this;
    }

    /**
     * Cборка лога из кастомной информации об обновляемой записи
     *
     * @param $tableName
     * @param $className
     * @param $recordId
     * @param array $fields
     * @param null $logType
     */
    public function fromCustomData($tableName, $className, $recordId, array $fields = [], $logType = null)
    {
        $this->prepareLogTable(
            $tableName,
            $className,
            $recordId,
            $logType
        );

        $this->prepareLogFields($fields);
    }

    /**
     * Заполняет поле logTable свежим экземпляром лога
     * @param $tableName
     * @param $className
     * @param $recordId
     * @param $logType
     */
    protected function prepareLogTable($tableName, $className, $recordId, $logType = null)
    {
        $logModelClass = $this->getLogsTableClass();

        $this->logTable = new $logModelClass([
            'table'         => $tableName,
            'class_name'    => $className,
            'record_id'     => $recordId,
            'user_id'       => $this->getCurrentUserId(),
            'log_type'      => $logType ?: $this->getDefaultLogType(),
        ]);

        try {
            if (isset(\Yii::$app->request)) {
                $this->logTable->user_ip    = \Yii::$app->request->getUserIP();
                $this->logTable->user_agent = \Yii::$app->request->getUserAgent();
                $this->logTable->referer    = \Yii::$app->request->getReferrer();
            }

            if (isset(\Yii::$app->controller)) {
                $this->logTable->controller_name    = \Yii::$app->controller::className();
                $this->logTable->action_name        = \Yii::$app->controller->action->id;
            }

        } catch (\Exception $ex) {
            //silence is golden
        }
    }

    /**
     * Подготовка записей логирующих измененные поля
     * @param array $fields
     */
    protected function prepareLogFields(array $fields)
    {
        if (empty($fields)) {
            return;
        }

        $recordArray = $this->removeIgnoredFields($fields);

        $logFieldsModelClass    = $this->getLogFieldsTableClass();

        foreach ($recordArray as $key => $value) {
            $this->logTableFields[$key] = new $logFieldsModelClass([
                'key' => $key,
                'value' => is_string($value) ? $value : Json::encode($value),
            ]);
        }
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function attributesChanged(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            if (array_key_exists($key, $this->logTableFields) && $this->logTableFields[$key] instanceof TablesLogFields) {
                $this->logTableFields[$key]->value_previous = (string)$attributes[$key];
            }
        }
        return $this;
    }

    /**
     * @param string $action
     * @return bool
     */
    public function storeLog($action = 'create')
    {
        if (empty($this->logTable) || !($this->logTable instanceof TablesLogs)) {
            \Yii::error('Попытка сохранить лог без лога');
            return false;
        }

        $this->logTable->action = $action;
        if (!$this->logTable->save()) {
            \Yii::error('Не удалось сохранить лог ' . Json::encode($this->logTable) . ' из-за ошибки: ' . Json::encode($this->logTable->getErrorSummary(1)));
            return false;
        }

        foreach ($this->logTableFields as $key => &$field) {
            if ($action !== self::ACTION_CREATE && $field->value_previous === null) {
                continue;
            }

            $field->log_id = $this->logTable->id;

            if (!$field->save()) {
                \Yii::error('Не удалось сохранить поле ' . $key . ' в логе: ' . Json::encode($field->getErrorSummary(1)));
            }
        }

        return true;
    }
}