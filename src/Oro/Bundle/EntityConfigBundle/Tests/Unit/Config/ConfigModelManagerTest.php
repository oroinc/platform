<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\SchemaManagerMock;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class ConfigModelManagerTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY = 'Test\Entity\TestEntity';
    const TEST_FIELD  = 'testField';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var ConfigModelManager */
    protected $configModelManager;

    public function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $serviceLink = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();
        $serviceLink->expects($this->any())
            ->method('getService')
            ->will($this->returnValue($this->em));

        $this->configModelManager = new ConfigModelManager($serviceLink);
    }

    public function testGetEntityManager()
    {
        $this->assertSame($this->em, $this->configModelManager->getEntityManager());
    }

    public function testCheckDatabaseException()
    {
        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->once())
            ->method('isConnected')
            ->will($this->returnValue(false));
        $connection->expects($this->once())
            ->method('connect')
            ->will($this->throwException(new \PDOException()));

        $this->em->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connection));

        $this->assertFalse($this->configModelManager->checkDatabase());
    }

    /**
     * @dataProvider checkDatabaseProvider
     */
    public function testCheckDatabase(array $tables, $expectedResult)
    {
        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->exactly(2))
            ->method('isConnected')
            ->will($this->returnValue(true));
        $connection->expects($this->once())
            ->method('getConfiguration')
            ->will($this->returnValue(new Configuration()));
        $connection->expects($this->once())
            ->method('getDatabasePlatform')
            ->will($this->returnValue(new MySqlPlatform()));
        $connection->expects($this->once())
            ->method('getSchemaManager')
            ->will($this->returnValue(new SchemaManagerMock($connection)));
        $connection->expects($this->once())
            ->method('fetchAll')
            ->will($this->returnValue($tables));

        $this->em->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connection));

        $this->assertEquals($expectedResult, $this->configModelManager->checkDatabase());
    }

    public function checkDatabaseProvider()
    {
        return [
            [
                [
                    'other_table',
                    'oro_entity_config',
                    'oro_entity_config_field',
                    'oro_entity_config_index_value',
                ],
                true
            ],
            [
                [
                    'other_table',
                    'oro_entity_config',
                    'oro_entity_config_field',
                ],
                false
            ],
            [
                [],
                false
            ],
        ];
    }

    /**
     * @dataProvider emptyNameProvider
     */
    public function testFindEntityModelEmptyClassName($className)
    {
        $this->assertNull($this->configModelManager->findEntityModel($className));
    }

    /**
     * @dataProvider emptyNameProvider
     */
    public function testFindFieldModelEmptyClassName($className)
    {
        $this->assertNull($this->configModelManager->findFieldModel($className, self::TEST_FIELD));
    }

    /**
     * @dataProvider emptyNameProvider
     */
    public function testFindFieldModelEmptyFieldName($fieldName)
    {
        $this->assertNull($this->configModelManager->findFieldModel(self::TEST_ENTITY, $fieldName));
    }

    /**
     * @dataProvider ignoredEntitiesProvider
     */
    public function testFindEntityModelIgnore($className)
    {
        $this->assertNull(
            $this->configModelManager->findEntityModel($className)
        );
    }

    /**
     * @dataProvider ignoredEntitiesProvider
     */
    public function testFindFieldModelIgnore($className)
    {
        $this->assertNull(
            $this->configModelManager->findFieldModel($className, self::TEST_FIELD)
        );
    }

    public function ignoredEntitiesProvider()
    {
        return [
            ['Oro\Bundle\EntityConfigBundle\Entity\AbstractConfigModel'],
            ['Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel'],
            ['Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel'],
            ['Oro\Bundle\EntityConfigBundle\Entity\ConfigModelIndexValue'],
        ];
    }

    public function testFindEntityModel()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);

        $repo = $this->createRepositoryMock(
            [$entityModel],
            [UnitOfWork::STATE_MANAGED, UnitOfWork::STATE_MANAGED]
        );
        $repo->expects($this->never())
            ->method('findOneBy');

        $this->assertSame(
            $entityModel,
            $this->configModelManager->findEntityModel(self::TEST_ENTITY)
        );

        // test localCache
        $this->assertSame(
            $entityModel,
            $this->configModelManager->findEntityModel(self::TEST_ENTITY)
        );

        // test non configurable entity
        $this->assertNull(
            $this->configModelManager->findEntityModel('Test\Entity\AnotherEntity')
        );
    }

    public function testFindEntityModelDetached()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);

        $repo = $this->createRepositoryMock(
            [$entityModel, $this->createEntityModel('Test\Entity\AnotherEntity')],
            [
                UnitOfWork::STATE_DETACHED,
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED,
            ]
        );
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['className' => self::TEST_ENTITY])
            ->will($this->returnValue($entityModel));

        $this->assertSame(
            $entityModel,
            $this->configModelManager->findEntityModel(self::TEST_ENTITY)
        );

        // test localCache
        $this->assertSame(
            $entityModel,
            $this->configModelManager->findEntityModel(self::TEST_ENTITY)
        );
    }

    public function testFindEntityModelAllDetached()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);

        $repo = $this->createRepositoryMock(
            [$entityModel, $this->createEntityModel('Test\Entity\AnotherEntity')],
            [
                UnitOfWork::STATE_DETACHED,
                UnitOfWork::STATE_DETACHED,
                UnitOfWork::STATE_DETACHED,
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED,
            ],
            2
        );
        $repo->expects($this->never())
            ->method('findOneBy');

        $this->assertSame(
            $entityModel,
            $this->configModelManager->findEntityModel(self::TEST_ENTITY)
        );

        // test localCache
        $this->assertSame(
            $entityModel,
            $this->configModelManager->findEntityModel(self::TEST_ENTITY)
        );
    }

    public function testFindFieldModel()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);
        $fieldModel  = $this->createFieldModel($entityModel, self::TEST_FIELD);

        $repo = $this->createRepositoryMock(
            [$entityModel],
            [
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED,
            ]
        );
        $repo->expects($this->never())
            ->method('findOneBy');

        $this->assertSame(
            $fieldModel,
            $this->configModelManager->findFieldModel(self::TEST_ENTITY, self::TEST_FIELD)
        );

        // test localCache
        $this->assertSame(
            $fieldModel,
            $this->configModelManager->findFieldModel(self::TEST_ENTITY, self::TEST_FIELD)
        );

        // test for non configurable field
        $this->assertNull(
            $this->configModelManager->findFieldModel(self::TEST_ENTITY, 'anotherField')
        );
    }

    public function testFindFieldModelDetachedEntity()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);
        $fieldModel  = $this->createFieldModel($entityModel, self::TEST_FIELD);

        $repo = $this->createRepositoryMock(
            [$entityModel, $this->createEntityModel('Test\Entity\AnotherEntity')],
            [
                UnitOfWork::STATE_DETACHED,
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED,
            ]
        );
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['className' => self::TEST_ENTITY])
            ->will($this->returnValue($entityModel));

        $this->assertSame(
            $fieldModel,
            $this->configModelManager->findFieldModel(self::TEST_ENTITY, self::TEST_FIELD)
        );

        // test localCache
        $this->assertSame(
            $fieldModel,
            $this->configModelManager->findFieldModel(self::TEST_ENTITY, self::TEST_FIELD)
        );

        // test localCache for another field
        $this->assertNull(
            $this->configModelManager->findFieldModel(self::TEST_ENTITY, 'anotherField')
        );
    }

    public function testFindFieldModelAllEntitiesDetached()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);
        $fieldModel  = $this->createFieldModel($entityModel, self::TEST_FIELD);

        $repo = $this->createRepositoryMock(
            [$entityModel, $this->createEntityModel('Test\Entity\AnotherEntity')],
            [
                UnitOfWork::STATE_DETACHED,
                UnitOfWork::STATE_DETACHED,
                UnitOfWork::STATE_DETACHED,
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED,
            ],
            2
        );
        $repo->expects($this->never())
            ->method('findOneBy');

        $this->assertSame(
            $fieldModel,
            $this->configModelManager->findFieldModel(self::TEST_ENTITY, self::TEST_FIELD)
        );

        // test localCache
        $this->assertSame(
            $fieldModel,
            $this->configModelManager->findFieldModel(self::TEST_ENTITY, self::TEST_FIELD)
        );

        // test localCache for another field
        $this->assertNull(
            $this->configModelManager->findFieldModel(self::TEST_ENTITY, 'anotherField')
        );
    }

    public function testFindFieldModelDetached()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);
        $fieldModel  = $this->createFieldModel($entityModel, self::TEST_FIELD);

        $repo = $this->createRepositoryMock(
            [$entityModel],
            [
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_DETACHED,
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED,
            ]
        );
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['className' => self::TEST_ENTITY])
            ->will($this->returnValue($entityModel));

        $this->assertSame(
            $fieldModel,
            $this->configModelManager->findFieldModel(self::TEST_ENTITY, self::TEST_FIELD)
        );

        // test localCache
        $this->assertSame(
            $fieldModel,
            $this->configModelManager->findFieldModel(self::TEST_ENTITY, self::TEST_FIELD)
        );

        // test localCache for another field
        $this->assertNull(
            $this->configModelManager->findFieldModel(self::TEST_ENTITY, 'anotherField')
        );
    }

    /**
     * @dataProvider emptyNameProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $className must not be empty
     */
    public function testGetEntityModelEmptyClassName($className)
    {
        $this->configModelManager->getEntityModel($className);
    }

    /**
     * @expectedException \Oro\Bundle\EntityConfigBundle\Exception\RuntimeException
     * @expectedExceptionMessage A model for "Test\Entity\TestEntity" was not found
     */
    public function testGetEntityModelForNonExistingEntity()
    {
        $this->createRepositoryMock();
        $this->configModelManager->getEntityModel(self::TEST_ENTITY);
    }

    public function testGetEntityModel()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);
        $this->createRepositoryMock(
            [$entityModel],
            [UnitOfWork::STATE_MANAGED]
        );

        $this->assertSame(
            $entityModel,
            $this->configModelManager->getEntityModel(self::TEST_ENTITY)
        );
    }

    /**
     * @dataProvider emptyNameProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $className must not be empty
     */
    public function testGetFieldModelEmptyClassName($className)
    {
        $this->configModelManager->getFieldModel($className, self::TEST_FIELD);
    }

    /**
     * @dataProvider emptyNameProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $fieldName must not be empty
     */
    public function testGetFieldModelEmptyFieldName($fieldName)
    {
        $this->configModelManager->getFieldModel(self::TEST_ENTITY, $fieldName);
    }

    /**
     * @expectedException \Oro\Bundle\EntityConfigBundle\Exception\RuntimeException
     * @expectedExceptionMessage A model for "Test\Entity\TestEntity::testField" was not found
     */
    public function testGetFieldModelForNonExistingEntity()
    {
        $this->createRepositoryMock();
        $this->configModelManager->getFieldModel(self::TEST_ENTITY, self::TEST_FIELD);
    }

    /**
     * @expectedException \Oro\Bundle\EntityConfigBundle\Exception\RuntimeException
     * @expectedExceptionMessage A model for "Test\Entity\TestEntity::testField" was not found
     */
    public function testGetFieldModelForNonExistingField()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);
        $this->createRepositoryMock(
            [$entityModel],
            [UnitOfWork::STATE_MANAGED]
        );

        $this->configModelManager->getFieldModel(self::TEST_ENTITY, self::TEST_FIELD);
    }

    public function testGetFieldEntityModel()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);
        $fieldModel  = $this->createFieldModel($entityModel, self::TEST_FIELD);
        $this->createRepositoryMock(
            [$entityModel],
            [UnitOfWork::STATE_MANAGED, UnitOfWork::STATE_MANAGED]
        );

        $this->assertSame(
            $fieldModel,
            $this->configModelManager->getFieldModel(self::TEST_ENTITY, self::TEST_FIELD)
        );
    }

    public function testGetEntityModelByConfigId()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);
        $this->createRepositoryMock(
            [$entityModel],
            [UnitOfWork::STATE_MANAGED]
        );

        $this->assertSame(
            $entityModel,
            $this->configModelManager->getModelByConfigId(new EntityConfigId('test', self::TEST_ENTITY))
        );
    }

    public function testGetFieldModelByConfigId()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);
        $fieldModel  = $this->createFieldModel($entityModel, self::TEST_FIELD);
        $this->createRepositoryMock(
            [$entityModel],
            [UnitOfWork::STATE_MANAGED, UnitOfWork::STATE_MANAGED]
        );

        $this->assertSame(
            $fieldModel,
            $this->configModelManager->getModelByConfigId(
                new FieldConfigId('test', self::TEST_ENTITY, self::TEST_FIELD, 'string')
            )
        );
    }

    public function testGetEntityModels()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);
        $this->createRepositoryMock([$entityModel]);

        $this->assertEquals(
            [$entityModel],
            $this->configModelManager->getModels()
        );
    }

    public function testGetFieldModels()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);
        $fieldModel  = $this->createFieldModel($entityModel, self::TEST_FIELD);
        $this->createRepositoryMock(
            [$entityModel],
            [UnitOfWork::STATE_MANAGED]
        );

        $this->assertEquals(
            [$fieldModel],
            $this->configModelManager->getModels(self::TEST_ENTITY)
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid $mode: "wrongMode"
     */
    public function testCreateEntityModelWithInvalidMode()
    {
        $this->configModelManager->createEntityModel(self::TEST_ENTITY, 'wrongMode');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid $mode: "wrongMode"
     */
    public function testCreateFieldModelWithInvalidMode()
    {
        $this->configModelManager->createFieldModel(
            self::TEST_ENTITY,
            self::TEST_FIELD,
            'string',
            'wrongMode'
        );
    }

    /**
     * @dataProvider emptyNameProvider
     */
    public function testCreateEntityModelEmptyClassName($className)
    {
        $expectedResult = new EntityConfigModel($className);
        $expectedResult->setMode(ConfigModelManager::MODE_DEFAULT);

        $result = $this->configModelManager->createEntityModel($className);
        $this->assertEquals($expectedResult, $result);

        // test that the created model is NOT stored in a local cache
        $this->setExpectedException(
            '\InvalidArgumentException',
            '$className must not be empty'
        );
        $this->configModelManager->getEntityModel($className);
    }

    public function testCreateEntityModel()
    {
        $expectedResult = new EntityConfigModel(self::TEST_ENTITY);
        $expectedResult->setMode(ConfigModelManager::MODE_DEFAULT);

        $this->createRepositoryMock([], [UnitOfWork::STATE_MANAGED]);

        $result = $this->configModelManager->createEntityModel(self::TEST_ENTITY);
        $this->assertEquals($expectedResult, $result);

        // test that the created model is stored in a local cache
        $this->assertSame($result, $this->configModelManager->getEntityModel(self::TEST_ENTITY));
    }

    /**
     * @dataProvider emptyNameProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $className must not be empty
     */
    public function testCreateFieldModelEmptyClassName($className)
    {
        $this->configModelManager->createFieldModel($className, self::TEST_FIELD, 'int');
    }

    /**
     * @dataProvider emptyNameProvider
     */
    public function testCreateFieldModelEmptyFieldName($fieldName)
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);

        $expectedResult = new FieldConfigModel($fieldName, 'int');
        $expectedResult->setMode(ConfigModelManager::MODE_DEFAULT);
        $expectedResult->setEntity($entityModel);

        $this->createRepositoryMock(
            [$entityModel],
            [UnitOfWork::STATE_MANAGED]
        );

        $result = $this->configModelManager->createFieldModel(
            self::TEST_ENTITY,
            $fieldName,
            'int',
            ConfigModelManager::MODE_DEFAULT
        );
        $this->assertEquals($expectedResult, $result);

        // test that the created model is NOT stored in a local cache
        $this->setExpectedException(
            '\InvalidArgumentException',
            '$fieldName must not be empty'
        );
        $this->configModelManager->getFieldModel(self::TEST_ENTITY, $fieldName);
    }

    public function testCreateFieldModel()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);

        $expectedResult = new FieldConfigModel(self::TEST_FIELD, 'int');
        $expectedResult->setMode(ConfigModelManager::MODE_DEFAULT);
        $expectedResult->setEntity($entityModel);

        $this->createRepositoryMock(
            [$entityModel],
            [
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED
            ]
        );

        $result = $this->configModelManager->createFieldModel(
            self::TEST_ENTITY,
            self::TEST_FIELD,
            'int',
            ConfigModelManager::MODE_DEFAULT
        );
        $this->assertEquals($expectedResult, $result);

        // test that the created model is stored in a local cache
        $this->assertSame(
            $result,
            $this->configModelManager->getFieldModel(self::TEST_ENTITY, self::TEST_FIELD)
        );
    }

    /**
     * @dataProvider emptyNameProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $className must not be empty
     */
    public function testChangeFieldNameEmptyClassName($className)
    {
        $this->configModelManager->changeFieldName(
            $className,
            self::TEST_FIELD,
            'newField'
        );
    }

    /**
     * @dataProvider emptyNameProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $fieldName must not be empty
     */
    public function testChangeFieldNameEmptyFieldName($fieldName)
    {
        $this->configModelManager->changeFieldName(
            self::TEST_ENTITY,
            $fieldName,
            'newField'
        );
    }

    /**
     * @dataProvider emptyNameProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $newFieldName must not be empty
     */
    public function testChangeFieldNameEmptyNewFieldName($newFieldName)
    {
        $this->configModelManager->changeFieldName(
            self::TEST_ENTITY,
            self::TEST_FIELD,
            $newFieldName
        );
    }

    public function testChangeFieldNameWithTheSameName()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);
        $fieldModel  = $this->createFieldModel($entityModel, self::TEST_FIELD);
        $this->createRepositoryMock(
            [$entityModel],
            [
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED
            ]
        );

        $this->em->expects($this->never())
            ->method('persist');
        $this->em->expects($this->never())
            ->method('flush');

        $result = $this->configModelManager->changeFieldName(
            self::TEST_ENTITY,
            self::TEST_FIELD,
            self::TEST_FIELD
        );
        $this->assertFalse($result);

        $this->assertEquals(
            self::TEST_FIELD,
            $this->configModelManager->getFieldModel(self::TEST_ENTITY, self::TEST_FIELD)->getFieldName()
        );
    }

    public function testChangeFieldName()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);
        $fieldModel  = $this->createFieldModel($entityModel, self::TEST_FIELD);
        $this->createRepositoryMock(
            [$entityModel],
            [
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED
            ]
        );

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->equalTo($fieldModel));
        $this->em->expects($this->never())
            ->method('flush');

        $result = $this->configModelManager->changeFieldName(
            self::TEST_ENTITY,
            self::TEST_FIELD,
            'newField'
        );
        $this->assertTrue($result);

        $this->assertEquals(
            'newField',
            $this->configModelManager->getFieldModel(self::TEST_ENTITY, 'newField')->getFieldName()
        );
        $this->assertNull(
            $this->configModelManager->findFieldModel(self::TEST_ENTITY, self::TEST_FIELD)
        );
    }

    /**
     * @dataProvider emptyNameProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $className must not be empty
     */
    public function testChangeFieldTypeEmptyClassName($className)
    {
        $this->configModelManager->changeFieldType(
            $className,
            self::TEST_FIELD,
            'int'
        );
    }

    /**
     * @dataProvider emptyNameProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $fieldName must not be empty
     */
    public function testChangeFieldTypeEmptyFieldName($fieldName)
    {
        $this->configModelManager->changeFieldType(
            self::TEST_ENTITY,
            $fieldName,
            'int'
        );
    }

    /**
     * @dataProvider emptyNameProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $fieldType must not be empty
     */
    public function testChangeFieldTypeEmptyFieldType($fieldType)
    {
        $this->configModelManager->changeFieldType(
            self::TEST_ENTITY,
            self::TEST_FIELD,
            $fieldType
        );
    }

    public function testChangeFieldTypeWithTheSameType()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);
        $fieldModel  = $this->createFieldModel($entityModel, self::TEST_FIELD);
        $this->createRepositoryMock(
            [$entityModel],
            [
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED
            ]
        );

        $this->em->expects($this->never())
            ->method('persist');
        $this->em->expects($this->never())
            ->method('flush');

        $result = $this->configModelManager->changeFieldType(
            self::TEST_ENTITY,
            self::TEST_FIELD,
            'string'
        );
        $this->assertFalse($result);

        $this->assertEquals(
            $fieldModel->getType(),
            $this->configModelManager->getFieldModel(self::TEST_ENTITY, self::TEST_FIELD)->getType()
        );
    }

    public function testChangeFieldType()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);
        $fieldModel  = $this->createFieldModel($entityModel, self::TEST_FIELD);
        $this->createRepositoryMock(
            [$entityModel],
            [
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED
            ]
        );

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->equalTo($fieldModel));
        $this->em->expects($this->never())
            ->method('flush');

        $result = $this->configModelManager->changeFieldType(
            self::TEST_ENTITY,
            self::TEST_FIELD,
            'int'
        );
        $this->assertTrue($result);

        $this->assertEquals(
            'int',
            $this->configModelManager->getFieldModel(self::TEST_ENTITY, self::TEST_FIELD)->getType()
        );
    }

    public function emptyNameProvider()
    {
        return [
            [null],
            [''],
        ];
    }

    /**
     * @param EntityConfigModel[] $entityModels
     * @param array               $entityStates
     * @param int                 $findAllCount
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createRepositoryMock($entityModels = [], $entityStates = [], $findAllCount = 1)
    {
        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->exactly($findAllCount))
            ->method('findAll')
            ->will($this->returnValue($entityModels));
        $this->em->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repo));
        $this->prepareCheckDetached($entityStates);

        return $repo;
    }

    /**
     * @param array $entityStates
     */
    protected function prepareCheckDetached($entityStates)
    {
        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->any())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $uow->expects($this->exactly(count($entityStates)))
            ->method('getEntityState')
            ->will(new \PHPUnit_Framework_MockObject_Stub_ConsecutiveCalls($entityStates));
    }

    /**
     * @param string $className
     * @return EntityConfigModel
     */
    public static function createEntityModel($className)
    {
        return new EntityConfigModel($className);
    }

    /**
     * @param EntityConfigModel $entityModel
     * @param string            $fieldName
     * @return FieldConfigModel
     */
    public static function createFieldModel($entityModel, $fieldName)
    {
        $fieldModel = new FieldConfigModel($fieldName, 'string');
        $entityModel->addField($fieldModel);

        return $fieldModel;
    }
}
