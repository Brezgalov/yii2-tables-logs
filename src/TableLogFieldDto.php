<?php

namespace Brezgalov\TablesLogs;

class TableLogFieldDto
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $logId;

    /**
     * @var string
     */
    public $key;

    /**
     * @var string
     */
    public $value;

    /**
     * @var string
     */
    public $valuePrevious;

    /**
     * @return string[]
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'log_id' => $this->logId,
            'key' => $this->key,
            'value' => $this->value,
            'value_previous' => $this->valuePrevious,
        ];
    }
}