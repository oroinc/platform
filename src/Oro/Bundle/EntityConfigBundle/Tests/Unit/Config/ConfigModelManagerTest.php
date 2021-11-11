<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Config\ConfigDatabaseChecker;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityConfigBundle\Config\LockObject;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModelIndexValue;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;
use Oro\Component\Testing\ReflectionUtil;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ConfigModelManagerTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_ENTITY = 'Test\Entity\TestEntity';
    private const TEST_ENTITY2 = 'Test\Entity\TestEntity2';
    private const TEST_FIELD = 'testField';
    private const TEST_FIELD2 = 'testField2';

    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repo;

    /** @var ConfigDatabaseChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $databaseChecker;

    /** @var ConfigModelManager */
    private $configModelManager;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repo = $this->createMock(EntityRepository::class);
        $this->databaseChecker = $this->createMock(ConfigDatabaseChecker::class);

        $this->em->expects($this->any())
            ->method('getRepository')
            ->with(EntityConfigModel::class)
            ->willReturn($this->repo);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(EntityConfigModel::class)
            ->willReturn($this->em);

        $this->configModelManager = new ConfigModelManager(
            $doctrine,
            new LockObject(),
            $this->databaseChecker
        );
    }

    public function testGetEntityManager()
    {
        $this->assertSame($this->em, $this->configModelManager->getEntityManager());
    }

    public function testCheckDatabase()
    {
        $this->databaseChecker->expects(self::once())
            ->method('checkDatabase')
            ->willReturn(true);
        $this->assertTrue($this->configModelManager->checkDatabase());
    }

    public function testFindEntityModelEmptyClassName()
    {
        $this->assertNull($this->configModelManager->findEntityModel(''));
    }

    public function testFindFieldModelEmptyClassName()
    {
        $this->assertNull($this->configModelManager->findFieldModel('', self::TEST_FIELD));
    }

    public function testFindFieldModelEmptyFieldName()
    {
        $this->assertNull($this->configModelManager->findFieldModel(self::TEST_ENTITY, ''));
    }

    /**
     * @dataProvider ignoredEntitiesProvider
     */
    public function testFindEntityModelIgnore(string $className)
    {
        $this->assertNull(
            $this->configModelManager->findEntityModel($className)
        );
    }

    /**
     * @dataProvider ignoredEntitiesProvider
     */
    public function testFindFieldModelIgnore(string $className)
    {
        $this->assertNull(
            $this->configModelManager->findFieldModel($className, self::TEST_FIELD)
        );
    }

    public function ignoredEntitiesProvider(): array
    {
        return [
            [ConfigModel::class],
            [EntityConfigModel::class],
            [FieldConfigModel::class],
            [ConfigModelIndexValue::class],
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
            ->willReturn($entityModel);

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
            ->willReturn($entityModel);

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
            ->willReturn($entityModel);

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
            ->willReturnMap([
                [['className' => self::TEST_ENTITY], null, $entityModel1],
                [['className' => self::TEST_ENTITY2], null, $entityModel2]
            ]);

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
            ->willReturn(null);
        $this->repo->expects($this->once())
            ->method('findAll')
            ->willReturn([$entityModel1, $entityModel2]);

        $this->prepareCheckDetached([UnitOfWork::STATE_MANAGED, UnitOfWork::STATE_MANAGED]);

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
            ->willReturn($entityModel0);

        $query = $this->createMock(AbstractQuery::class);
        $qb = $this->createMock(QueryBuilder::class);
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
            ->willReturn([$entityModel1, $entityModel2]);

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
        $fieldModel = $this->createFieldModel($entityModel, self::TEST_FIELD);

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
        $fieldModel = $this->createFieldModel($entityModel, self::TEST_FIELD);

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
            ->willReturn($entityModel);

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
        $fieldModel = $this->createFieldModel($entityModel, self::TEST_FIELD);

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
            ->willReturn($entityModel);

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
        $fieldModel = $this->createFieldModel($entityModel, self::TEST_FIELD);

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
            ->willReturn($entityModel);

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

    public function testGetEntityModelEmptyClassName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$className must not be empty');

        $this->configModelManager->getEntityModel('');
    }

    public function testGetEntityModelForNonExistingEntity()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A model for "Test\Entity\TestEntity" was not found');

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

    public function testGetFieldModelEmptyClassName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$className must not be empty');

        $this->configModelManager->getFieldModel('', self::TEST_FIELD);
    }

    public function testGetFieldModelEmptyFieldName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$fieldName must not be empty');

        $this->configModelManager->getFieldModel(self::TEST_ENTITY, '');
    }

    public function testGetFieldModelForNonExistingEntity()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A model for "Test\Entity\TestEntity::testField" was not found');

        $this->prepareEntityConfigRepository();
        $this->configModelManager->getFieldModel(self::TEST_ENTITY, self::TEST_FIELD);
    }

    public function testGetFieldModelForNonExistingField()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A model for "Test\Entity\TestEntity::testField" was not found');

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
        $fieldModel = $this->createFieldModel($entityModel, self::TEST_FIELD);
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
            ->willReturn([$entityModel1, $entityModel2]);

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
            ->willReturn($entityModel);
        $this->prepareCheckDetached([UnitOfWork::STATE_MANAGED]);

        $this->assertEquals(
            [$fieldModel1, $fieldModel2],
            $this->configModelManager->getModels(self::TEST_ENTITY)
        );
    }

    public function testCreateEntityModelWithInvalidMode()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid $mode: "wrongMode"');

        $this->configModelManager->createEntityModel(self::TEST_ENTITY, 'wrongMode');
    }

    public function testCreateFieldModelWithInvalidMode()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid $mode: "wrongMode"');

        $this->configModelManager->createFieldModel(
            self::TEST_ENTITY,
            self::TEST_FIELD,
            'string',
            'wrongMode'
        );
    }

    public function testCreateEntityModelEmptyClassName()
    {
        $className = '';

        $expectedResult = new EntityConfigModel($className);
        $expectedResult->setMode(ConfigModel::MODE_DEFAULT);

        $result = $this->configModelManager->createEntityModel($className);
        $this->assertEquals($expectedResult, $result);

        // test that the created model is NOT stored in a local cache
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$className must not be empty');
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

    public function testCreateFieldModelEmptyClassName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$className must not be empty');

        $this->configModelManager->createFieldModel('', self::TEST_FIELD, 'int');
    }

    public function testCreateFieldModelEmptyFieldName()
    {
        $fieldName = '';

        $entityModel = $this->createEntityModel(self::TEST_ENTITY);

        $expectedResult = new FieldConfigModel($fieldName, 'int');
        $expectedResult->setMode(ConfigModel::MODE_DEFAULT);
        $expectedResult->setEntity($entityModel);

        $this->prepareEntityConfigRepository(
            [$entityModel],
            [UnitOfWork::STATE_MANAGED]
        );

        $result = $this->configModelManager->createFieldModel(self::TEST_ENTITY, $fieldName, 'int');
        $this->assertEquals($expectedResult, $result);

        // test that the created model is NOT stored in a local cache
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$fieldName must not be empty');
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

        $result = $this->configModelManager->createFieldModel(self::TEST_ENTITY, self::TEST_FIELD, 'int');
        $this->assertEquals($expectedResult, $result);

        // test that the created model is stored in a local cache
        $this->assertSame(
            $result,
            $this->configModelManager->getFieldModel(self::TEST_ENTITY, self::TEST_FIELD)
        );
    }

    public function testChangeFieldNameEmptyClassName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$className must not be empty');

        $this->configModelManager->changeFieldName('', self::TEST_FIELD, 'newField');
    }

    public function testChangeFieldNameEmptyFieldName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$fieldName must not be empty');

        $this->configModelManager->changeFieldName(self::TEST_ENTITY, '', 'newField');
    }

    public function testChangeFieldNameEmptyNewFieldName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$newFieldName must not be empty');

        $this->configModelManager->changeFieldName(self::TEST_ENTITY, self::TEST_FIELD, '');
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
        $fieldModel = $this->createFieldModel($entityModel, self::TEST_FIELD);
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
            ->with($this->identicalTo($fieldModel));
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

    public function testChangeFieldTypeEmptyClassName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$className must not be empty');

        $this->configModelManager->changeFieldType('', self::TEST_FIELD, 'int');
    }

    public function testChangeFieldTypeEmptyFieldName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$fieldName must not be empty');

        $this->configModelManager->changeFieldType(self::TEST_ENTITY, '', 'int');
    }

    public function testChangeFieldTypeEmptyFieldType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$fieldType must not be empty');

        $this->configModelManager->changeFieldType(self::TEST_ENTITY, self::TEST_FIELD, '');
    }

    public function testChangeFieldTypeWithTheSameType()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);
        $fieldModel = $this->createFieldModel($entityModel, self::TEST_FIELD);
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
        $fieldModel = $this->createFieldModel($entityModel, self::TEST_FIELD);
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
            ->with($this->identicalTo($fieldModel));
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

    public function testChangeFieldModeEmptyClassName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$className must not be empty');

        $this->configModelManager->changeFieldMode('', self::TEST_FIELD, ConfigModel::MODE_HIDDEN);
    }

    public function testChangeFieldModeEmptyFieldName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$fieldName must not be empty');

        $this->configModelManager->changeFieldMode(self::TEST_ENTITY, '', ConfigModel::MODE_HIDDEN);
    }

    public function testChangeFieldModeEmptyMode()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$mode must not be empty');

        $this->configModelManager->changeFieldMode(self::TEST_ENTITY, self::TEST_FIELD, '');
    }

    public function testChangeFieldModeWithTheSameMode()
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);
        $fieldModel = $this->createFieldModel($entityModel, self::TEST_FIELD);
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
        $fieldModel = $this->createFieldModel($entityModel, self::TEST_FIELD);
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
            ->with($this->identicalTo($fieldModel));
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

    public function testChangeEntityModeEmptyClassName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$className must not be empty');

        $this->configModelManager->changeEntityMode('', ConfigModel::MODE_HIDDEN);
    }

    public function testChangeEntityModeEmptyMode()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$mode must not be empty');

        $this->configModelManager->changeEntityMode(self::TEST_ENTITY, '');
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
            ->with($this->identicalTo($entityModel));
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

    private function prepareEntityConfigRepository(array $entityModels = [], array $entityStates = []): void
    {
        $this->repo->expects($this->once())
            ->method('findAll')
            ->willReturn($entityModels);

        $this->prepareCheckDetached($entityStates);

        $this->configModelManager->getModels();
    }

    private function prepareCheckDetached(array $entityStates)
    {
        $uow = $this->createMock(UnitOfWork::class);
        $this->em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects($this->exactly(count($entityStates)))
            ->method('getEntityState')
            ->willReturnOnConsecutiveCalls(...$entityStates);
    }

    private function createEntityModel(string $className): EntityConfigModel
    {
        return new EntityConfigModel($className);
    }

    private function createFieldModel(EntityConfigModel $entityModel, string $fieldName): FieldConfigModel
    {
        $fieldModel = new FieldConfigModel($fieldName, 'string');
        $entityModel->addField($fieldModel);

        return $fieldModel;
    }
}
