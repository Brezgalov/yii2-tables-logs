<?php

namespace Brezgalov\TablesLogs;

use yii\base\Model;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
 * Class TableLoggerForm
 * @package Brezgalov\TablesLogs
 */
class TableLoggerForm extends Model
{
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';

    const LOG_TYPE_DEFAULT = 'default';

    /**
     * @var TablesLogs
     */
    public $logTable;

    /**
     * @var string
     */
    public $logType = self::LOG_TYPE_DEFAULT;

    /**
     * @var TablesLogFields[]
     */
    public $logTableFields = [];

    /**
     * @var array
     */
    public $fieldsToIgnore = [
        'id',
        'updated_at',
        'created_at',
    ];

    /**
     * @var int
     */
    public $currentUserId;

    /**
     * @var bool
     */
    public $filterUnchengedAttributes = true;

    /**
     * @var bool
     */
    public $logOnNoChanges = false;

    /**
     * @var bool
     */
    public $ignoreRequestFetchErrors = true;

    /**
     * @var bool
     */
    public $ignoreControllerFetchErrors = true;

    /**
     * @var string
     */
    public $defaultLogType = self::LOG_TYPE_DEFAULT;

    /**
     * @param array $params
     * @return TableLoggerForm
     */
    public static function createLog(array $params = [])
    {
        return new static($params);
    }

    /**
     * @param array $fields
     * @return $this
     */
    public function setIgnoredFields(array $fields)
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
        if ($this->currentUserId) {
            return $this->currentUserId;
        }

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
     * @param null $primaryKeyField
     * @return $this
     */
    public function fromRecord(ActiveRecord $record, $primaryKeyField = null)
    {
        if (empty($primaryKeyField)) {
            $pKeys = array_keys($record->getPrimaryKey(true));
            $primaryKeyField = array_shift($pKeys);
        }

        $this->prepareLogTable(
            $record::tableName(),
            $record::className(),
            @$record->{$primaryKeyField} ?: null,
            $this->logType
        );

        return $this;
    }

    /**
     * @param $action
     * @return $this
     */
    public function setAction($action)
    {
        if (empty($this->logTable)) {
            throw new \Exception('logTable not instantinated');
        }

        $this->logTable->action = $action;

        return $this;
    }

    /**
     * @param $logType
     * @return $this
     */
    public function setLogType($logType)
    {
        $this->logType = $logType;

        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLogOnNoAttributes($value)
    {
        $this->logOnNoChanges = (bool)$value;

        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFilterUnchangedAttributes($value)
    {
        $this->filterUnchengedAttributes = (bool)$value;

        return $this;
    }

    /**
     * Cборка лога из кастомной информации об обновляемой записи
     *
     * @param $tableName
     * @param $className
     * @param $recordId
     * @return $this
     */
    public function fromCustomData($tableName, $className, $recordId)
    {
        $this->prepareLogTable(
            $tableName,
            $className,
            $recordId,
            $this->logType
        );

        return $this;
    }

    /**
     * @param array $values Values in format of [<field> => <value>]
     * @return $this
     */
    public function setPreviousValues(array $values)
    {
        return $this->setLogFieldsValues($values, 'value_previous');
    }

    /**
     * @param array $values Values in format of [<field> => <value>]
     * @return $this
     */
    public function setCurrentValues(array $values)
    {
        return $this->setLogFieldsValues($values, 'value');
    }

    /**
     * @param array $values
     * @param $fieldToSet
     * @return $this
     * @throws \Exception
     */
    protected function setLogFieldsValues(array $values, $fieldToSet)
    {
        // Это более эффективно, чем каждый раз делать if in_array($fieldName, $this->fieldsToIgnore)
        foreach ($this->fieldsToIgnore as $field) {
            unset($values[$field]);
        }

        foreach ($values as $fieldName => $value) {
            $fieldLog = $this->getLogField($fieldName);

            $fieldLog->{$fieldToSet} = $value;

            $this->logTableFields[$fieldName] = $fieldLog;
        }

        return $this;
    }

    /**
     * @param $fieldName
     * @return TablesLogFields|mixed
     * @throws \Exception
     */
    protected function getLogField($fieldName)
    {
        $log = ArrayHelper::getValue($this->logTableFields, $fieldName);

        if (empty($log)) {
            $log = new TablesLogFields();
            $log->key = $fieldName;
        }

        return $log;
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
            'action'        => static::ACTION_CREATE,
        ]);

        try {
            if (isset(\Yii::$app->request)) {
                $this->logTable->user_ip    = \Yii::$app->request->getUserIP();
                $this->logTable->user_agent = \Yii::$app->request->getUserAgent();
                $this->logTable->referer    = \Yii::$app->request->getReferrer();
            }
        } catch (\Exception $ex) {
            if (!$this->ignoreRequestFetchErrors) {
                throw $ex;
            }
        }

        try {
            if (isset(\Yii::$app->controller)) {
                $this->logTable->controller_name    = \Yii::$app->controller::className();

                if (isset(\Yii::$app->controller->action)) {
                    $this->logTable->action_name = \Yii::$app->controller->action->id;
                }
            }
        }  catch (\Exception $ex) {
            if (!$this->ignoreControllerFetchErrors) {
                throw $ex;
            }
        }
    }

    /**
     * @return bool
     */
    public function storeLog()
    {
        if (empty($this->logTable) || !($this->logTable instanceof TablesLogs)) {
            \Yii::error('Попытка сохранить лог без лога');
            return false;
        }

        $logFields = $this->logTableFields;

        if ($this->filterUnchengedAttributes) {
            foreach ($logFields as $fieldName => $field) {
                if ($field->value == $field->value_previous) {
                    unset($logFields[$fieldName]);
                }
            }
        }

        // Не сохраняем лог, если не было изменений в полях
        if (!$this->logOnNoChanges && empty($logFields)) {
            return true;
        }

        if (!$this->logTable->save()) {
            \Yii::error('Не удалось сохранить лог ' . Json::encode($this->logTable) . ' из-за ошибки: ' . Json::encode($this->logTable->getErrorSummary(1)));
            return false;
        }

        foreach ($logFields as $key => &$field) {
            $field->log_id = $this->logTable->id;

            if (!$field->save()) {
                \Yii::error('Не удалось сохранить поле ' . $key . ' в логе: ' . Json::encode($field->getErrorSummary(1)));
            }
        }

        return true;
    }
}