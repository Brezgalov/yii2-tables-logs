<?php

namespace Brezgalov\TablesLogs;

use yii\base\Model;
use yii\db\ActiveRecord;
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
        'created_at',
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
     * @return $this
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

        return $this;
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
        } catch (\Exception $ex) {
            //silence is golden
        }

        try {
            if (isset(\Yii::$app->controller)) {
                $this->logTable->controller_name    = \Yii::$app->controller::className();
                $this->logTable->action_name        = \Yii::$app->controller->action->id;
            }
        }  catch (\Exception $ex) {
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
            if (!is_null($value)) {
                if (!is_string($value)) {
                    $value = Json::encode($value);
                }
            }

            $this->logTableFields[$key] = new $logFieldsModelClass([
                'key' => $key,
                'value' => $value,
                'value_previous' => $value,
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
                $nextVal = null;

                // NULL turns to "null" with string convert
                if (!is_null($attributes[$key])) {
                    $nextVal = $attributes[$key];

                    if (!is_string($nextVal)) {
                        $nextVal = Json::encode($nextVal);
                    }
                }

                $this->logTableFields[$key]->value_previous = $nextVal;
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

        // Нужно задавать action через билдер ->action('update')
        // Тогда можно будет не добавлять заведомо ненужные поля в attributesChanged
        // @TODO: refactor

        $isUpdate = $action == static::ACTION_UPDATE;

        $logFields = $this->logTableFields;
        foreach ($logFields as $key => &$field) {
            // костыль
            // Либа довольно старая, сейчас нет времени ее нормально рефакторить
            // Раньше при создании полей value_previous заполнялось только в changedAttribures
            // Из-за этого было не понятно какое поле обновилось с NULL до значения, а какое не изменилось
            // Тут был unset
            // Теперь при создании из записи мы сразу заполняем value_previous тем же, что и value
            // А при создании просто не сохраняем эту инфу
            // т.о можно сохранять только измененные поля без рефактора этого пакета

            if ($action == static::ACTION_CREATE) {
                $field->value_previous = null;
            }

            // значение изменилось, если оно разное. Тут был баг
            if ($field->value_previous === $field->value) {
                unset($logFields[$key]);
            }
        }

        if ($isUpdate && empty($logFields)) {
            return true;
        }

        $this->logTable->action = $action;
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