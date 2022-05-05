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
    public $logger = TableLoggerForm::class;

    /**
     * @var string[]
     */
    public $ignoredFields = ['id'];

    /**
     * @var bool
     */
    public $filterUnchangedFieldsOnUpdate = true;

    /**
     * @var bool
     */
    public $logAttributesOnDelete = false;

    /**
     * @var string
     */
    public $logType = TableLoggerForm::LOG_TYPE_DEFAULT;

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
    protected function prepareLogger(ActiveRecord $record)
    {
        $loggerInstance = \Yii::createObject($this->logger);
        if (!($loggerInstance instanceof TableLoggerForm)) {
            throw new \Exception('Логер должен быть унаследован от ' . TableLoggerForm::class);
        }

        if (!empty($this->ignoredFields)) {
            $loggerInstance->setIgnoredFields($this->ignoredFields);
        }

        return $loggerInstance
            ->setLogType($this->logType)
            ->fromRecord($record);
    }

    /**
     * @param AfterSaveEvent $e
     * @throws \Exception
     */
    public function storeLogAfterInsert(AfterSaveEvent $e)
    {
        $this->prepareLogger($e->sender)
            ->setAction($this->createActionName)
            ->setFilterUnchangedAttributes(false) // Не нужно проверять изменилось значение или нет, если его раньше 100% не было
            ->setLogOnNoAttributes(true) // Записи у которых нет ничего кроме полей, которые не логируются (например id), тоже должны попасть в лог
            ->setCurrentValues($e->sender->attributes)
            ->storeLog();
    }

    /**
     * @param AfterSaveEvent $e
     * @throws \Exception
     */
    public function storeLogAfterUpdate(AfterSaveEvent $e)
    {
        /**
         * oldAttributes в ActiveRecord сбрасываются после Save и начинают соответствовать текущим
         *
         * Можно было бы сделать
         * $oldAttributes = array_merge($e->sender->attributes, $e->changedAttributes);
         * $this->setCurrentValues($e->sender->attributes)->setPreviousValues($oldAttributes)
         *
         * Представим что полей 25, а изменилось 1
         * Тогда мы заведомо 2 раза подряд записываем одинаковые значения 24 полей в память,
         * чтобы пройти по ним циклом и удалить совпадения
         *
         * Вместо этого можно взять сразу только измененные поля
         */

        $loger = $this
            ->prepareLogger($e->sender)
            ->setAction($this->updateActionName);

        if ($this->filterUnchangedFieldsOnUpdate) {
            $fieldsChanged = array_keys($e->changedAttributes);
            $attributes = $e->sender->toArray($fieldsChanged);

            $loger->setCurrentValues($attributes)->setPreviousValues($e->changedAttributes);
        } else {
            $oldAttributes = array_merge($e->sender->attributes, $e->changedAttributes);

            $loger
                ->setCurrentValues($e->sender->attributes)
                ->setPreviousValues($oldAttributes)
                ->setFilterUnchangedAttributes(false);
        };

        $loger->storeLog();
    }

    /**
     * @param Event $e
     * @throws \Exception
     */
    public function storeLogAfterDelete(Event $e)
    {
        $loger = $this->prepareLogger($e->sender)
            ->setAction($this->deleteActionName)
            ->setLogOnNoAttributes(true);

        if ($this->logAttributesOnDelete) {
            $loger
                ->setPreviousValues($e->sender->attributes)
                ->setCurrentValues($e->sender->attributes)
                ->setFilterUnchangedAttributes(false);
        }

        $loger->storeLog();
    }
}