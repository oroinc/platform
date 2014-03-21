<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Fixture\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Fixture\DemoEntity;

class FoundEntityConfigRepository extends EntityRepository
{
    protected static $configEntity;
    protected static $configField;

    public function findOneBy(array $criteria, array $orderBy = null)
    {
        if (isset($criteria['fieldName'])) {
            return self::getResultConfigField();
        } else {
            return self::getResultConfigEntity();
        }
    }

    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        return [self::getResultConfigField()];
    }

    public function findAll()
    {
        return array(self::getResultConfigEntity());
    }

    public static function getResultConfigEntity()
    {
        if (!self::$configEntity) {
            self::$configEntity = new EntityConfigModel(DemoEntity::ENTITY_NAME);

            self::$configEntity->addField(self::getResultConfigField());
            self::$configEntity->fromArray(
                'test',
                [
                    'test_value'              => 'test_value_origin',
                    'test_value_serializable' => ['test_value' => 'test_value_origin']
                ],
                ['test_value' => true]
            );
        }

        return self::$configEntity;
    }

    public static function getResultConfigField()
    {
        if (!self::$configField) {
            self::$configField = new FieldConfigModel('testField', 'string');
            self::$configField->fromArray(
                'test',
                [
                    'test_value' => 'test_value_origin'
                ],
                ['test_value' => true]
            );
        }

        return self::$configField;
    }
}
