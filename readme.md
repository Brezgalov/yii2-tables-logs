##Как пользоваться

1. Применить миграции
2. Использовать LoggerTrait в модели ActiveRecord

Пример использования:

    class Orders extends ActiveRecord
    {
        use LoggerTrait;

        public function afterSave($insert, $changedAttributes)
        {    
            if ($insert) {
                $this->logCreate();
            } else {
                $this->logUpdate($changedAttributes);
            }
        }
    }
    
##Использование в модулях и базах с префиксом
**Пункт 1.** Унаследуйте базовые модели и пропишите между ними связи. В примере используется префикс таблиц "tq_" и подключение к БД "trucksQueueModuleDbConnection"    
    
    class TablesLogFields extends \Brezgalov\TablesLogs\TablesLogFields
    {
        /**
         * {@inheritdoc}
         */
        public static function tableName()
        {
            return 'tq_tables_log_fields';
        }
    
        /**
         * @return \yii\db\Connection the database connection used by this AR class.
         */
        public static function getDb()
        {
            return \Yii::$app->get('trucksQueueModuleDbConnection');
        }
    
        /**
         * {@inheritdoc}
         */
        public function rules()
        {
            return [
                [['log_id'], 'required'],
                [['log_id'], 'integer'],
                [['key', 'value', 'value_previous'], 'string', 'max' => 255],
                [['log_id'], 'exist', 'skipOnError' => true, 'targetClass' => TablesLogs::class, 'targetAttribute' => ['log_id' => 'id']],
            ];
        }
    
        /**
         * @return \yii\db\ActiveQuery
         */
        public function getLog()
        {
            return $this->hasOne(TablesLogs::className(), ['id' => 'log_id']);
        }
    }
    
**Пункт 2.** Унаследуйте TableLoggerForm и пропишите использование кастомных моделей

    class TableLoggerForm extends \Brezgalov\TablesLogs\TableLoggerForm
    {    
        /**
         * @return string
         */
        public function getLogFieldsTableClass()
        {
            return TablesLogFields::class;
        }
    
        /**
         * @return string
         */
        public function getLogsTableClass()
        {
            return TablesLogs::class;
        }
    }
    
**Пункт 3.** Используйте LoggerTrait с указанием кастомного логера:


    class Quotas extends \app\modules\trucksqueue\models\base\Quotas
    {
        use LoggerTrait;
        
        /**
         * @param bool $insert
         * @param array $changedAttributes
         */
        public function afterSave($insert, $changedAttributes)
        {
            if ($insert) {
                $this->logCreate();
            } else {
                $this->logUpdate($changedAttributes);
            }
    
            parent::afterSave($insert, $changedAttributes);
        }
    
        /**
         * @return string
         */
        public function getTableLoggerClass()
        {
            return TableLoggerForm::class;
        }
    }