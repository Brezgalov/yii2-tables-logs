## Как пользоваться

1. Подключить миграции в конфиге yii2:


    'controllerMap' => [
        'migrate-logger' => [
            'class' => 'yii\console\controllers\MigrateController',
            'migrationPath' => [
                '@vendor/brezgalov/yii2-tables-logs/migrations'
            ],
        ],
    ],


2. Применить миграции
   

    php yii migrate-logger


3. Использовать LoggerBehavior в модели ActiveRecord

Пример использования:

    class Orders extends ActiveRecord
    {
        public function behaviors()
        {
            return array_merge(parent::behaviors(), [
                [
                    'class' => LoggerBehavior::class,
                ],
            ]);
        }
    }
    
## Использование в модулях и базах с префиксом
**Пункт 1.** Унаследуйте базовые модели и пропишите между ними связи. В примере используется префикс таблиц "tq_" и подключение к БД "trucksQueueModuleDbConnection"    
    
    class MyTablesLogFields extends \Brezgalov\TablesLogs\TablesLogFields
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

    class MyTableLoggerForm extends \Brezgalov\TablesLogs\TableLoggerForm
    {    
        /**
         * @return string
         */
        public function getLogFieldsTableClass()
        {
            return MyTablesLogFields::class;
        }
    
        /**
         * @return string
         */
        public function getLogsTableClass()
        {
            return MyTablesLogs::class;
        }
    }
    
**Пункт 3.** Используйте LoggerBehavior с указанием кастомного логера:


    [   
        'class' => LoggerBehavior::class,
        'loggerClass' => MyTableLoggerForm::class,
    ]

## Начиная с версии 3.0 
> * полностью удален LoggerTrait
> * проведен рефактор TableLoggerForm, новая версия не совместима
> * исправлен баг с отсутствием регистрации удаления записей
> * LoggerBehavior получил настройку **_filterUnchangedFieldsOnUpdate (= true)_** - при значении false, поведение записывает все поля записи в лог, независимо от того были в них изменения или нет, - это можно использовать, если необходимо выводить или хранить изменения записи в виде shapshot\'ов или иметь возможность откатить запись, до определенного состояния
> * LoggerBehavior получил настройку **_logAttributesOnDelete (= false)_** - при значении true, поведение будет регистрировать поля записи в логе при удалении, - это можно использовать для упрощения восстановления удаленных записей