<?php

namespace Brezgalov\TablesLogs;

class TableLogDto
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $table;

    /**
     * @var string
     */
    public $logType;

    /**
     * @var string
     */
    public $action;

    /**
     * @var string
     */
    public $className;

    /**
     * @var int
     */
    public $recordId;

    /**
     * @var int
     */
    public $userId;

    /**
     * @var string
     */
    public $userIp;

    /**
     * @var string
     */
    public $userAgent;

    /**
     * @var string
     */
    public $referer;

    /**
     * @var string
     */
    public $controllerName;

    /**
     * @var string
     */
    public $actionName;

    /**
     * @var string
     */
    public $createdAt;

    /**
     * @return string[]
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'table' => $this->table,
            'log_type' => $this->logType,
            'action' => $this->action,
            'class_name' => $this->className,
            'record_id' => $this->recordId,
            'user_id' => $this->userId,
            'user_ip' => $this->userIp,
            'user_agent' => $this->userAgent,
            'referer' => $this->referer,
            'controller_name' => $this->controllerName,
            'action_name' => $this->actionName,
            'created_at' => $this->createdAt,
        ];
    }
}