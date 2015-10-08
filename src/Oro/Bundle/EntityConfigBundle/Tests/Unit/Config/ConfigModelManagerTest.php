<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityConfigBundle\Config\LockObject;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

use Oro\Bundle\EntityConfigBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\SchemaManagerMock;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class ConfigModelManagerTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY  = 'Test\Entity\TestEntity';
    const TEST_ENTITY2 = 'Test\Entity\TestEntity2';
    const TEST_FIELD   = 'testField';
    const TEST_FIELD2  = 'testField2';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $repo;

    /** @var LockObject */
    protected $lockObject;

    /** @var ConfigModelManager */
    protected $configModelManager;

    protected function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->any())
            ->method('getRepository')
            ->with('Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel')
            ->will($this->returnValue($this->repo));

        $emLink = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();
        $emLink->expects($this->any())
            ->method('getService')
            ->will($this->returnValue($this->em));

        $this->lockObject = new LockObject();

        $this->configModelManager = new ConfigModelManager($emLink, $this->lockObject);
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
     * @dataProvider emptyValueProvider
     */
    public function testFindEntityModelEmptyClassName($className)
    {
        $this->assertNull($this->configModelManager->findEntityModel($className));
    }

    /**
     * @dataProvider emptyValueProvider
     */
    public function testFindFieldModelEmptyClassName($className)
    {
        $this->assertNull($this->configModelManager->findFieldModel($className, self::TEST_FIELD));
    }

    /**
     * @dataProvider emptyValueProvider
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
            ['Oro\Bundle\EntityConfigBundle\Entity\ConfigModel'],
            ['Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel'],
            ['Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel'],
            ['Oro\Bundle\EntityConfigBundle\Entity\ConfigModelIndexValue'],
        ];
    }

    public function testFindEntityModel()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);

        $this->prepareEntityConfigRepository(
            [$entityModel],
            [UnitOfWork::STATE_MANAGED, UnitOfWork::STATE_MANAGED]
        );
        $this->repo->expects($this->never())
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

        $this->prepareEntityConfigRepository(
            [$entityModel, $this->createEntityModel('Test\Entity\AnotherEntity')],
            [
                UnitOfWork::STATE_DETACHED,
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED,
            ]
        );
        $this->repo->expects($this->once())
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

        $this->prepareEntityConfigRepository(
            [$entityModel, $this->createEntityModel('Test\Entity\AnotherEntity')],
            [
                UnitOfWork::STATE_DETACHED,
                UnitOfWork::STATE_DETACHED,
                UnitOfWork::STATE_DETACHED,
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED,
            ]
        );
        $this->repo->expects($this->once())
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

    public function testFindEntityModelWhenNoAnyModelIsLoadedYet()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);

        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['className' => self::TEST_ENTITY])
            ->will($this->returnValue($entityModel));

        $this->prepareCheckDetached([UnitOfWork::STATE_MANAGED, UnitOfWork::STATE_MANAGED]);

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

    public function testFindEntityModelLoadSeveralModelCheckThatAllOfThemLoadedSeparately()
    {
        $entityModel1 = $this->createEntityModel(self::TEST_ENTITY);
        $entityModel2 = $this->createEntityModel(self::TEST_ENTITY2);

        $this->repo->expects($this->exactly(2))
            ->method('findOneBy')
            ->willReturnMap(
                [
                    [['className' => self::TEST_ENTITY], null, $entityModel1],
                    [['className' => self::TEST_ENTITY2], null, $entityModel2]
                ]
            );

        $this->prepareCheckDetached(
            [
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED
            ]
        );

        $this->assertSame(
            $entityModel1,
            $this->configModelManager->findEntityModel(self::TEST_ENTITY)
        );
        $this->assertSame(
            $entityModel2,
            $this->configModelManager->findEntityModel(self::TEST_ENTITY2)
        );

        // test localCache
        $this->assertSame(
            $entityModel1,
            $this->configModelManager->findEntityModel(self::TEST_ENTITY)
        );
        $this->assertSame(
            $entityModel2,
            $this->configModelManager->findEntityModel(self::TEST_ENTITY2)
        );
    }

    public function testThatEntityListAreLoadedAfterNonConfigurableEntityIsLoaded()
    {
        $entityModel1 = $this->createEntityModel(self::TEST_ENTITY);
        $entityModel2 = $this->createEntityModel(self::TEST_ENTITY2);

        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['className' => 'Test\NonConfigurableEntity'])
            ->will($this->returnValue(null));
        $this->repo->expects($this->once())
            ->method('findAll')
            ->will($this->returnValue([$entityModel1, $entityModel2]));

        $this->prepareCheckDetached(
            [
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED
            ]
        );

        $this->assertNull(
            $this->configModelManager->findEntityModel('Test\NonConfigurableEntity')
        );
        $this->assertEquals(
            [$entityModel1, $entityModel2],
            $this->configModelManager->getModels()
        );

        // test localCache
        $this->assertNull(
            $this->configModelManager->findEntityModel('Test\NonConfigurableEntity')
        );
        $this->assertSame(
            $entityModel1,
            $this->configModelManager->findEntityModel(self::TEST_ENTITY)
        );
        $this->assertSame(
            $entityModel2,
            $this->configModelManager->findEntityModel(self::TEST_ENTITY2)
        );
    }

    public function testThatEntityListAreLoadedAfterConfigurableEntityIsLoaded()
    {
        $entityModel0 = $this->createEntityModel('Test\ConfigurableEntity');
        ReflectionUtil::setId($entityModel0, 123);
        $entityModel1 = $this->createEntityModel(self::TEST_ENTITY);
        $entityModel2 = $this->createEntityModel(self::TEST_ENTITY2);

        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['className' => 'Test\ConfigurableEntity'])
            ->will($this->returnValue($entityModel0));

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($qb);
        $qb->expects($this->once())
            ->method('where')
            ->with('e.id NOT IN (:exclusions)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('exclusions', [$entityModel0->getId()])
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue([$entityModel1, $entityModel2]));

        $this->prepareCheckDetached(
            [
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED
            ]
        );

        $this->assertSame(
            $entityModel0,
            $this->configModelManager->findEntityModel('Test\ConfigurableEntity')
        );
        $this->assertEquals(
            [$entityModel0, $entityModel1, $entityModel2],
            $this->configModelManager->getModels()
        );

        // test localCache
        $this->assertSame(
            $entityModel0,
            $this->configModelManager->findEntityModel('Test\ConfigurableEntity')
        );
        $this->assertSame(
            $entityModel1,
            $this->configModelManager->findEntityModel(self::TEST_ENTITY)
        );
        $this->assertSame(
            $entityModel2,
            $this->configModelManager->findEntityModel(self::TEST_ENTITY2)
        );
    }

    public function testFindFieldModel()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);
        $fieldModel  = $this->createFieldModel($entityModel, self::TEST_FIELD);

        $this->prepareEntityConfigRepository(
            [$entityModel],
            [
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED,
            ]
        );
        $this->repo->expects($this->never())
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

        $this->prepareEntityConfigRepository(
            [$entityModel, $this->createEntityModel('Test\Entity\AnotherEntity')],
            [
                UnitOfWork::STATE_DETACHED,
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED,
            ]
        );
        $this->repo->expects($this->once())
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

        $this->prepareEntityConfigRepository(
            [$entityModel, $this->createEntityModel('Test\Entity\AnotherEntity')],
            [
                UnitOfWork::STATE_DETACHED,
                UnitOfWork::STATE_DETACHED,
                UnitOfWork::STATE_DETACHED,
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED,
            ]
        );
        $this->repo->expects($this->once())
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

    public function testFindFieldModelDetached()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);
        $fieldModel  = $this->createFieldModel($entityModel, self::TEST_FIELD);

        $this->prepareEntityConfigRepository(
            [$entityModel],
            [
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_DETACHED,
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED,
            ]
        );
        $this->repo->expects($this->once())
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
     * @dataProvider emptyValueProvider
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
        $this->prepareEntityConfigRepository();
        $this->configModelManager->getEntityModel(self::TEST_ENTITY);
    }

    public function testGetEntityModel()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);
        $this->prepareEntityConfigRepository(
            [$entityModel],
            [UnitOfWork::STATE_MANAGED]
        );

        $this->assertSame(
            $entityModel,
            $this->configModelManager->getEntityModel(self::TEST_ENTITY)
        );
    }

    /**
     * @dataProvider emptyValueProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $className must not be empty
     */
    public function testGetFieldModelEmptyClassName($className)
    {
        $this->configModelManager->getFieldModel($className, self::TEST_FIELD);
    }

    /**
     * @dataProvider emptyValueProvider
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
        $this->prepareEntityConfigRepository();
        $this->configModelManager->getFieldModel(self::TEST_ENTITY, self::TEST_FIELD);
    }

    /**
     * @expectedException \Oro\Bundle\EntityConfigBundle\Exception\RuntimeException
     * @expectedExceptionMessage A model for "Test\Entity\TestEntity::testField" was not found
     */
    public function testGetFieldModelForNonExistingField()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);
        $this->prepareEntityConfigRepository(
            [$entityModel],
            [UnitOfWork::STATE_MANAGED]
        );

        $this->configModelManager->getFieldModel(self::TEST_ENTITY, self::TEST_FIELD);
    }

    public function testGetFieldEntityModel()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);
        $fieldModel  = $this->createFieldModel($entityModel, self::TEST_FIELD);
        $this->prepareEntityConfigRepository(
            [$entityModel],
            [UnitOfWork::STATE_MANAGED, UnitOfWork::STATE_MANAGED]
        );

        $this->assertSame(
            $fieldModel,
            $this->configModelManager->getFieldModel(self::TEST_ENTITY, self::TEST_FIELD)
        );
    }

    public function testGetEntityModels()
    {
        $entityModel1 = $this->createEntityModel(self::TEST_ENTITY);
        $entityModel2 = $this->createEntityModel(self::TEST_ENTITY2);
        $entityModel2->setMode(ConfigModel::MODE_HIDDEN);

        $this->repo->expects($this->once())
            ->method('findAll')
            ->will($this->returnValue([$entityModel1, $entityModel2]));

        $this->assertEquals(
            [$entityModel1, $entityModel2],
            $this->configModelManager->getModels()
        );
    }

    public function testGetFieldModels()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);
        $fieldModel1 = $this->createFieldModel($entityModel, self::TEST_FIELD);
        $fieldModel2 = $this->createFieldModel($entityModel, self::TEST_FIELD2);
        $fieldModel2->setMode(ConfigModel::MODE_HIDDEN);

        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['className' => self::TEST_ENTITY])
            ->will($this->returnValue($entityModel));
        $this->prepareCheckDetached([UnitOfWork::STATE_MANAGED]);

        $this->assertEquals(
            [$fieldModel1, $fieldModel2],
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
     * @dataProvider emptyValueProvider
     */
    public function testCreateEntityModelEmptyClassName($className)
    {
        $expectedResult = new EntityConfigModel($className);
        $expectedResult->setMode(ConfigModel::MODE_DEFAULT);

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
        $expectedResult->setMode(ConfigModel::MODE_DEFAULT);

        $this->prepareEntityConfigRepository([], [UnitOfWork::STATE_MANAGED]);

        $result = $this->configModelManager->createEntityModel(self::TEST_ENTITY);
        $this->assertEquals($expectedResult, $result);

        // test that the created model is stored in a local cache
        $this->assertSame($result, $this->configModelManager->getEntityModel(self::TEST_ENTITY));
    }

    /**
     * @dataProvider emptyValueProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $className must not be empty
     */
    public function testCreateFieldModelEmptyClassName($className)
    {
        $this->configModelManager->createFieldModel($className, self::TEST_FIELD, 'int');
    }

    /**
     * @dataProvider emptyValueProvider
     */
    public function testCreateFieldModelEmptyFieldName($fieldName)
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);

        $expectedResult = new FieldConfigModel($fieldName, 'int');
        $expectedResult->setMode(ConfigModel::MODE_DEFAULT);
        $expectedResult->setEntity($entityModel);

        $this->prepareEntityConfigRepository(
            [$entityModel],
            [UnitOfWork::STATE_MANAGED]
        );

        $result = $this->configModelManager->createFieldModel(
            self::TEST_ENTITY,
            $fieldName,
            'int',
            ConfigModel::MODE_DEFAULT
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
        $expectedResult->setMode(ConfigModel::MODE_DEFAULT);
        $expectedResult->setEntity($entityModel);

        $this->prepareEntityConfigRepository(
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
            ConfigModel::MODE_DEFAULT
        );
        $this->assertEquals($expectedResult, $result);

        // test that the created model is stored in a local cache
        $this->assertSame(
            $result,
            $this->configModelManager->getFieldModel(self::TEST_ENTITY, self::TEST_FIELD)
        );
    }

    /**
     * @dataProvider emptyValueProvider
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
     * @dataProvider emptyValueProvider
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
     * @dataProvider emptyValueProvider
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
        $this->createFieldModel($entityModel, self::TEST_FIELD);
        $this->prepareEntityConfigRepository(
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
        $this->prepareEntityConfigRepository(
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
     * @dataProvider emptyValueProvider
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
     * @dataProvider emptyValueProvider
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
     * @dataProvider emptyValueProvider
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
        $this->prepareEntityConfigRepository(
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
        $this->prepareEntityConfigRepository(
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

    /**
     * @dataProvider emptyValueProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $className must not be empty
     */
    public function testChangeFieldModeEmptyClassName($className)
    {
        $this->configModelManager->changeFieldMode(
            $className,
            self::TEST_FIELD,
            ConfigModel::MODE_HIDDEN
        );
    }

    /**
     * @dataProvider emptyValueProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $fieldName must not be empty
     */
    public function testChangeFieldModeEmptyFieldName($fieldName)
    {
        $this->configModelManager->changeFieldMode(
            self::TEST_ENTITY,
            $fieldName,
            ConfigModel::MODE_HIDDEN
        );
    }

    /**
     * @dataProvider emptyValueProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $mode must not be empty
     */
    public function testChangeFieldModeEmptyMode($mode)
    {
        $this->configModelManager->changeFieldMode(
            self::TEST_ENTITY,
            self::TEST_FIELD,
            $mode
        );
    }

    public function testChangeFieldModeWithTheSameMode()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);
        $fieldModel  = $this->createFieldModel($entityModel, self::TEST_FIELD);
        $fieldModel->setMode(ConfigModel::MODE_HIDDEN);
        $this->prepareEntityConfigRepository(
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

        $result = $this->configModelManager->changeFieldMode(
            self::TEST_ENTITY,
            self::TEST_FIELD,
            ConfigModel::MODE_HIDDEN
        );
        $this->assertFalse($result);

        $this->assertEquals(
            $fieldModel->getMode(),
            $this->configModelManager->getFieldModel(self::TEST_ENTITY, self::TEST_FIELD)->getMode()
        );
    }

    public function testChangeFieldMode()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);
        $fieldModel  = $this->createFieldModel($entityModel, self::TEST_FIELD);
        $this->prepareEntityConfigRepository(
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

        $result = $this->configModelManager->changeFieldMode(
            self::TEST_ENTITY,
            self::TEST_FIELD,
            ConfigModel::MODE_HIDDEN
        );
        $this->assertTrue($result);

        $this->assertEquals(
            ConfigModel::MODE_HIDDEN,
            $this->configModelManager->getFieldModel(self::TEST_ENTITY, self::TEST_FIELD)->getMode()
        );
    }

    /**
     * @dataProvider emptyValueProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $className must not be empty
     */
    public function testChangeEntityModeEmptyClassName($className)
    {
        $this->configModelManager->changeEntityMode(
            $className,
            ConfigModel::MODE_HIDDEN
        );
    }

    /**
     * @dataProvider emptyValueProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $mode must not be empty
     */
    public function testChangeEntityModeEmptyMode($mode)
    {
        $this->configModelManager->changeEntityMode(
            self::TEST_ENTITY,
            $mode
        );
    }

    public function testChangeEntityModeWithTheSameMode()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);
        $entityModel->setMode(ConfigModel::MODE_HIDDEN);
        $this->prepareEntityConfigRepository(
            [$entityModel],
            [
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED
            ]
        );

        $this->em->expects($this->never())
            ->method('persist');
        $this->em->expects($this->never())
            ->method('flush');

        $result = $this->configModelManager->changeEntityMode(
            self::TEST_ENTITY,
            ConfigModel::MODE_HIDDEN
        );
        $this->assertFalse($result);

        $this->assertEquals(
            $entityModel->getMode(),
            $this->configModelManager->getEntityModel(self::TEST_ENTITY)->getMode()
        );
    }

    public function testChangeEntityMode()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);
        $this->prepareEntityConfigRepository(
            [$entityModel],
            [
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED
            ]
        );

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->equalTo($entityModel));
        $this->em->expects($this->never())
            ->method('flush');

        $result = $this->configModelManager->changeEntityMode(
            self::TEST_ENTITY,
            ConfigModel::MODE_HIDDEN
        );
        $this->assertTrue($result);

        $this->assertEquals(
            ConfigModel::MODE_HIDDEN,
            $this->configModelManager->getEntityModel(self::TEST_ENTITY)->getMode()
        );
    }

    public function emptyValueProvider()
    {
        return [
            [null],
            [''],
        ];
    }

    /**
     * @param EntityConfigModel[] $entityModels
     * @param array               $entityStates
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareEntityConfigRepository($entityModels = [], $entityStates = [])
    {
        $this->repo->expects($this->once())
            ->method('findAll')
            ->will($this->returnValue($entityModels));

        $this->prepareCheckDetached($entityStates);

        $this->configModelManager->getModels();
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
