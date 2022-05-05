<?php

namespace Brezgalov\TablesLogs;

interface ILogStorage
{
    /**
     * @param TableLogDto $logDto
     * @return bool
     */
    public function storeLog(TableLogDto $logDto);

    /**
     * @param TableLogFieldDto $logFieldDto
     * @return bool
     */
    public function storeLogFields(TableLogFieldDto $logFieldDto);

    /**
     * @param string $tableName
     * @return string
     */
    public function quoteTableName(string $tableName);
}