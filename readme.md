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