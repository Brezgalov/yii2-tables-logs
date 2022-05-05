<?php

namespace Brezgalov\TablesLogs;

class TableLogFieldDto
{
    public $id;
    public $log_id;
    public $key;
    public $value;
    public $value_previous;

    /**
     * @return string[]
     */
    public function toArray()
    {
        return [
            'id',
            'log_id',
            'key',
            'value',
            'value_previous',
        ];
    }
}