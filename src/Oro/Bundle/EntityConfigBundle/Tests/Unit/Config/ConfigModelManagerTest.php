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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ConfigModelManagerTest extends TestCase
{
    private const TEST_ENTITY = 'Test\Entity\TestEntity';
    private const TEST_ENTITY2 = 'Test\Entity\TestEntity2';
    private const TEST_FIELD = 'testField';
    private const TEST_FIELD2 = 'testField2';

    private EntityManagerInterface&MockObject $em;
    private EntityRepository&MockObject $repo;
    private ConfigDatabaseChecker&MockObject $databaseChecker;
    private ConfigModelManager $configModelManager;

    #[\Override]
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

    public function testGetEntityManager(): void
    {
        $this->assertSame($this->em, $this->configModelManager->getEntityManager());
    }

    public function testCheckDatabase(): void
    {
        $this->databaseChecker->expects(self::once())
            ->method('checkDatabase')
            ->willReturn(true);
        $this->assertTrue($this->configModelManager->checkDatabase());
    }

    public function testFindEntityModelEmptyClassName(): void
    {
        $this->assertNull($this->configModelManager->findEntityModel(''));
    }

    public function testFindFieldModelEmptyClassName(): void
    {
        $this->assertNull($this->configModelManager->findFieldModel('', self::TEST_FIELD));
    }

    public function testFindFieldModelEmptyFieldName(): void
    {
        $this->assertNull($this->configModelManager->findFieldModel(self::TEST_ENTITY, ''));
    }

    /**
     * @dataProvider ignoredEntitiesProvider
     */
    public function testFindEntityModelIgnore(string $className): void
    {
        $this->assertNull(
            $this->configModelManager->findEntityModel($className)
        );
    }

    /**
     * @dataProvider ignoredEntitiesProvider
     */
    public function testFindFieldModelIgnore(string $className): void
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

    public function testFindEntityModel(): void
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

    public function testFindEntityModelDetached(): void
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

    public function testFindEntityModelAllDetached(): void
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
        $this->prepareQueryBuilderForLoadEntityModels();

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

    public function testFindEntityModelWhenNoAnyModelIsLoadedYet(): void
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);

        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['className' => self::TEST_ENTITY])
            ->willReturn($entityModel);
        $this->prepareQueryBuilderForLoadEntityModels();
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

    public function testFindEntityModelLoadSeveralModelCheckThatAllOfThemLoaded(): void
    {
        $entityModel1 = $this->createEntityModel(self::TEST_ENTITY);
        $entityModel2 = $this->createEntityModel(self::TEST_ENTITY2);

        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['className' => self::TEST_ENTITY])
            ->willReturn($entityModel1);

        $this->repo->expects($this->never())
            ->method('findAll');

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
            ->with('exclusions', [$entityModel1->getId()])
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$entityModel2]);

        $this->prepareCheckDetached(
            [
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED,
                UnitOfWork::STATE_MANAGED,
            ]
        );

        $this->assertSame(
            $entityModel1,
            $this->configModelManager->findEntityModel(self::TEST_ENTITY)
        );

        // test that the second entity model is also loaded
        $this->assertSame(
            $entityModel2,
            $this->configModelManager->findEntityModel(self::TEST_ENTITY2)
        );

        // test localCache
        $this->assertSame(
            $entityModel1,
            $this->configModelManager->findEntityModel(self::TEST_ENTITY)
        );
    }

    public function testThatEntityListAreLoadedAfterNonConfigurableEntityIsLoaded(): void
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

    public function testThatEntityListAreLoadedAfterConfigurableEntityIsLoaded(): void
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

    public function testFindFieldModel(): void
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

    public function testFindFieldModelDetachedEntity(): void
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

    public function testFindFieldModelAllEntitiesDetached(): void
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);
        $fieldModel = $this->createFieldModel($entityModel, self::TEST_FIELD);

        $this->prepareQueryBuilderForLoadEntityModels();

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

    public function testFindFieldModelDetached(): void
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

    public function testGetEntityModelEmptyClassName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$className must not be empty');

        $this->configModelManager->getEntityModel('');
    }

    public function testGetEntityModelForNonExistingEntity(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A model for "Test\Entity\TestEntity" was not found');

        $this->prepareEntityConfigRepository();
        $this->configModelManager->getEntityModel(self::TEST_ENTITY);
    }

    public function testGetEntityModel(): void
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

    public function testGetFieldModelEmptyClassName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$className must not be empty');

        $this->configModelManager->getFieldModel('', self::TEST_FIELD);
    }

    public function testGetFieldModelEmptyFieldName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$fieldName must not be empty');

        $this->configModelManager->getFieldModel(self::TEST_ENTITY, '');
    }

    public function testGetFieldModelForNonExistingEntity(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A model for "Test\Entity\TestEntity::testField" was not found');

        $this->prepareEntityConfigRepository();
        $this->configModelManager->getFieldModel(self::TEST_ENTITY, self::TEST_FIELD);
    }

    public function testGetFieldModelForNonExistingField(): void
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

    public function testGetFieldEntityModel(): void
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

    public function testGetEntityModels(): void
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

    public function testGetFieldModels(): void
    {
        $entityModel = $this->createEntityModel(self::TEST_ENTITY);
        $fieldModel1 = $this->createFieldModel($entityModel, self::TEST_FIELD);
        $fieldModel2 = $this->createFieldModel($entityModel, self::TEST_FIELD2);
        $fieldModel2->setMode(ConfigModel::MODE_HIDDEN);

        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['className' => self::TEST_ENTITY])
            ->willReturn($entityModel);

        $this->prepareQueryBuilderForLoadEntityModels();
        $this->prepareCheckDetached([UnitOfWork::STATE_MANAGED]);

        $this->assertEquals(
            [$fieldModel1, $fieldModel2],
            $this->configModelManager->getModels(self::TEST_ENTITY)
        );
    }

    public function testCreateEntityModelWithInvalidMode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid $mode: "wrongMode"');

        $this->configModelManager->createEntityModel(self::TEST_ENTITY, 'wrongMode');
    }

    public function testCreateFieldModelWithInvalidMode(): void
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

    public function testCreateEntityModelEmptyClassName(): void
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

    public function testCreateEntityModel(): void
    {
        $expectedResult = new EntityConfigModel(self::TEST_ENTITY);
        $expectedResult->setMode(ConfigModel::MODE_DEFAULT);

        $this->prepareEntityConfigRepository([], [UnitOfWork::STATE_MANAGED]);

        $result = $this->configModelManager->createEntityModel(self::TEST_ENTITY);
        $this->assertEquals($expectedResult, $result);

        // test that the created model is stored in a local cache
        $this->assertSame($result, $this->configModelManager->getEntityModel(self::TEST_ENTITY));
    }

    public function testCreateFieldModelEmptyClassName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$className must not be empty');

        $this->configModelManager->createFieldModel('', self::TEST_FIELD, 'int');
    }

    public function testCreateFieldModelEmptyFieldName(): void
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

    public function testCreateFieldModel(): void
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

    public function testChangeFieldNameEmptyClassName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$className must not be empty');

        $this->configModelManager->changeFieldName('', self::TEST_FIELD, 'newField');
    }

    public function testChangeFieldNameEmptyFieldName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$fieldName must not be empty');

        $this->configModelManager->changeFieldName(self::TEST_ENTITY, '', 'newField');
    }

    public function testChangeFieldNameEmptyNewFieldName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$newFieldName must not be empty');

        $this->configModelManager->changeFieldName(self::TEST_ENTITY, self::TEST_FIELD, '');
    }

    public function testChangeFieldNameWithTheSameName(): void
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

    public function testChangeFieldName(): void
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

    public function testChangeFieldTypeEmptyClassName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$className must not be empty');

        $this->configModelManager->changeFieldType('', self::TEST_FIELD, 'int');
    }

    public function testChangeFieldTypeEmptyFieldName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$fieldName must not be empty');

        $this->configModelManager->changeFieldType(self::TEST_ENTITY, '', 'int');
    }

    public function testChangeFieldTypeEmptyFieldType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$fieldType must not be empty');

        $this->configModelManager->changeFieldType(self::TEST_ENTITY, self::TEST_FIELD, '');
    }

    public function testChangeFieldTypeWithTheSameType(): void
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

    public function testChangeFieldType(): void
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

    public function testChangeFieldModeEmptyClassName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$className must not be empty');

        $this->configModelManager->changeFieldMode('', self::TEST_FIELD, ConfigModel::MODE_HIDDEN);
    }

    public function testChangeFieldModeEmptyFieldName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$fieldName must not be empty');

        $this->configModelManager->changeFieldMode(self::TEST_ENTITY, '', ConfigModel::MODE_HIDDEN);
    }

    public function testChangeFieldModeEmptyMode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$mode must not be empty');

        $this->configModelManager->changeFieldMode(self::TEST_ENTITY, self::TEST_FIELD, '');
    }

    public function testChangeFieldModeWithTheSameMode(): void
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

    public function testChangeFieldMode(): void
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

    public function testChangeEntityModeEmptyClassName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$className must not be empty');

        $this->configModelManager->changeEntityMode('', ConfigModel::MODE_HIDDEN);
    }

    public function testChangeEntityModeEmptyMode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$mode must not be empty');

        $this->configModelManager->changeEntityMode(self::TEST_ENTITY, '');
    }

    public function testChangeEntityModeWithTheSameMode(): void
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

    public function testChangeEntityMode(): void
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

    private function prepareQueryBuilderForLoadEntityModels(): void
    {
        $query = $this->createMock(AbstractQuery::class);
        $qbMock = $this->createMock(QueryBuilder::class);
        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($qbMock);
        $qbMock->expects($this->once())
            ->method('where')
            ->willReturnSelf();
        $qbMock->expects($this->once())
            ->method('setParameter')
            ->willReturnSelf();
        $qbMock->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);
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
