<?php

namespace Brezgalov\TablesLogs;


use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "tables_logs".
 *
 * @property int $id
 * @property string $table
 * @property string $log_type
 * @property string $action
 * @property string $class_name
 * @property int $record_id
 * @property int $user_id
 * @property string $user_ip
 * @property string $user_agent
 * @property string $referer
 * @property string $controller_name
 * @property string $action_name
 * @property string $created_at
 *
 * @property TablesLogFields[] $tablesLogFields
 */
class TablesLogs extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tables_logs';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['table', 'action'], 'required'],
            [['record_id', 'user_id'], 'integer'],
            [['created_at'], 'safe'],
            [['table', 'log_type', 'action', 'class_name', 'user_ip', 'user_agent', 'referer', 'controller_name', 'action_name'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'table' => 'Table',
            'log_type' => 'Log Type',
            'action' => 'Action',
            'class_name' => 'Class Name',
            'record_id' => 'Record ID',
            'user_id' => 'User ID',
            'user_ip' => 'User Ip',
            'user_agent' => 'User Agent',
            'referer' => 'Referer',
            'controller_name' => 'Controller Name',
            'action_name' => 'Action Name',
            'created_at' => 'Created At',
        ];
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            [
                'class'         => TimestampBehavior::class,
                'attributes'    => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                ],
                'value'         => function() {
                    return date('Y-m-d H:i:s');
                },
            ]
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTablesLogFields()
    {
        return $this->hasMany(TablesLogFields::className(), ['log_id' => 'id']);
    }
}
