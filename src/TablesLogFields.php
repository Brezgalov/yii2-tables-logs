<?php

namespace Brezgalov\TablesLogs;

use Yii;

/**
 * This is the model class for table "tables_log_fields".
 *
 * @property int $id
 * @property int $log_id
 * @property string $key
 * @property string $value
 * @property string $value_previous
 *
 * @property TablesLogs $log
 */
class TablesLogFields extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tables_log_fields';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['log_id'], 'required'],
            [['key', 'value', 'value_previous'], 'safe'],
            [['log_id'], 'exist', 'skipOnError' => true, 'targetClass' => TablesLogs::class, 'targetAttribute' => ['log_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'log_id' => 'Log ID',
            'key' => 'Key',
            'value' => 'Value',
            'value_previous' => 'Value Previous',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLog()
    {
        return $this->hasOne(TablesLogs::class, ['id' => 'log_id']);
    }
}
