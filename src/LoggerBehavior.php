<?php

namespace Brezgalov\TablesLogs;

use yii\base\Behavior;
use yii\base\Event;
use yii\base\ModelEvent;
use yii\db\ActiveRecord;
use yii\db\AfterSaveEvent;

/**
 * Class LoggerBehavior
 * @package Brezgalov\TablesLogs
 */
class LoggerBehavior extends Behavior
{
    /**
     * @var string
     */
    public $loggerClass = TableLoggerForm::class;

    /**
     * @var string[]
     */
    public $ignoredFields = ['id'];

    /**
     * @var string
     */
    public $logType = 'default';

    /**
     * @var string
     */
    public $createActionName = TableLoggerForm::ACTION_CREATE;

    /**
     * @var string
     */
    public $updateActionName = TableLoggerForm::ACTION_UPDATE;

    /**
     * @var string
     */
    public $deleteActionName = TableLoggerForm::ACTION_DELETE;

    /**
     * @return string[]
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'storeLogAfterInsert',
            ActiveRecord::EVENT_AFTER_UPDATE => 'storeLogAfterUpdate',
            ActiveRecord::EVENT_AFTER_DELETE => 'storeLogAfterDelete',
        ];
    }

    /**
     * @param ActiveRecord $record
     * @return TableLoggerForm
     */
    public function prepareLogger(ActiveRecord $record)
    {
        $loggerInstance = \Yii::$container->get($this->loggerClass);
        if (!($loggerInstance instanceof TableLoggerForm)) {
            throw new \Exception('Логер должен быть унаследован от ' . TableLoggerForm::class);
        }

        $loggerInstance->fromRecord($record, $this->logType);

        if (!empty($this->ignoredFields)) {
            $loggerInstance->ignoreFields($this->ignoredFields);
        }

        return $loggerInstance;
    }

    public function storeLogAfterInsert(AfterSaveEvent $e)
    {
        $this->prepareLogger($e->sender)
            ->storeLog($this->createActionName);
    }

    public function storeLogAfterUpdate(AfterSaveEvent $e)
    {
        $this->prepareLogger($e->sender)
            ->attributesChanged($e->changedAttributes)
            ->storeLog($this->updateActionName);
    }

    public function storeLogAfterDelete(Event $e)
    {
        $this->prepareLogger($e->sender)
            ->storeLog($this->deleteActionName);
    }
}