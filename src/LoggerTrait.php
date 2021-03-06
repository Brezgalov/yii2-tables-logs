<?php

namespace Brezgalov\TablesLogs;

/**
 * Trait LoggerTrait is deprecated
 * Use LoggerBehavior
 * @package Brezgalov\TablesLogs
 * @deprecated
 */
trait LoggerTrait
{
    /**
     * @var string|null
     */
    public $logType = null;

    /**
     * @return string
     */
    public function getTableLoggerClass()
    {
        return TableLoggerForm::class;
    }

    /**
     * @return array
     */
    public function getLogFieldsIgnored()
    {
        return [
            array_keys($this->getPrimaryKey(true))
        ];
    }

    /**
     * @return TableLogger
     */
    protected function getLogger()
    {
        $loggerClass = $this->getTableLoggerClass();
        $logger = $loggerClass::createLog()->fromRecord($this, $this->logType);

        $logFieldsIgnored = $this->getLogFieldsIgnored();
        if (!empty($logFieldsIgnored)) {
            $logger->ignoreFields($logFieldsIgnored);
        }

        return $logger;
    }

    /**
     * @deprecated
     * @return bool
     */
    public function logCreate()
    {
        return $this->getLogger()->storeLog();
    }

    /**
     * @deprecated
     * @param $changedAttributes
     * @return bool
     */
    public function logUpdate($changedAttributes)
    {
        $loggerClass = $this->getTableLoggerClass();

        return $this->getLogger()
            ->attributesChanged($changedAttributes)
            ->storeLog($loggerClass::ACTION_UPDATE);
    }

    /**
     * @deprecated
     * @return bool
     */
    public function logDelete()
    {
        $loggerClass = $this->getTableLoggerClass();

        return $this->getLogger()->storeLog($loggerClass::ACTION_DELETE);
    }
}