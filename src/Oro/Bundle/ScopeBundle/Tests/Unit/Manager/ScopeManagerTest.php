<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Exception\NotSupportedCriteriaValueException;
use Oro\Bundle\ScopeBundle\Manager\ScopeCollection;
use Oro\Bundle\ScopeBundle\Manager\ScopeDataAccessor;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\ScopeBundle\Tests\Unit\Stub\StubContext;
use Oro\Bundle\ScopeBundle\Tests\Unit\Stub\StubScope;
use Oro\Bundle\ScopeBundle\Tests\Unit\Stub\StubScopeCriteriaProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ScopeManagerTest extends TestCase
{
    private ScopeDataAccessor&MockObject $scopeDataAccessor;
    private ScopeCollection&MockObject $scheduledForInsertScopes;
    private ManagerRegistry&MockObject $doctrine;
    private EntityManagerInterface&MockObject $em;
    private ClassMetadataFactory&MockObject $classMetadataFactory;
    private ClassMetadata&MockObject $scopeClassMetadata;

    #[\Override]
    protected function setUp(): void
    {
        $this->scopeDataAccessor = $this->createMock(ScopeDataAccessor::class);
        $this->scheduledForInsertScopes = $this->createMock(ScopeCollection::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->classMetadataFactory = $this->createMock(ClassMetadataFactory::class);
        $this->scopeClassMetadata = $this->createMock(ClassMetadata::class);

        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(Scope::class)
            ->willReturn($this->em);
        $this->em->expects($this->any())
            ->method('getMetadataFactory')
            ->willReturn($this->classMetadataFactory);
        $this->classMetadataFactory->expects($this->any())
            ->method('getMetadataFor')
            ->with(Scope::class)
            ->willReturn($this->scopeClassMetadata);
    }

    private function getScopeManager(array $providers = []): ScopeManager
    {
        $serviceMap = [];
        $providerIds = [];
        foreach ($providers as $scopeType => $services) {
            $serviceIds = [];
            foreach ($services as $key => $service) {
                $serviceId = sprintf('%s_%s', $scopeType, $key);
                $serviceIds[] = $serviceId;
                $serviceMap[$serviceId] = $service;
            }
            $providerIds[$scopeType] = $serviceIds;
        }

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($serviceId) use ($serviceMap) {
                if (!isset($serviceMap[$serviceId])) {
                    throw new ServiceNotFoundException($serviceId);
                }

                return $serviceMap[$serviceId];
            });

        return new ScopeManager(
            $providerIds,
            $container,
            $this->doctrine,
            $this->scopeDataAccessor,
            $this->scheduledForInsertScopes,
            PropertyAccess::createPropertyAccessor()
        );
    }

    public function testFindDefaultScope(): void
    {
        $expectedCriteria = new ScopeCriteria(['relation' => null], $this->classMetadataFactory);
        $scope = new Scope();

        $this->scopeClassMetadata->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(['relation']);

        $this->scopeDataAccessor->expects($this->once())
            ->method('findOneByCriteria')
            ->with($expectedCriteria)
            ->willReturn($scope);

        $manager = $this->getScopeManager();
        $this->assertSame($scope, $manager->findDefaultScope());
    }

    public function testGetCriteriaByScope(): void
    {
        $scope = new StubScope();
        $scope->setScopeField('expected_value');

        $this->scopeClassMetadata->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn([]);

        $provider = new StubScopeCriteriaProvider('scopeField', new \stdClass(), \stdClass::class);

        $manager = $this->getScopeManager(['testScope' => [$provider]]);
        $this->assertSame(
            [$provider->getCriteriaField() => 'expected_value'],
            $manager->getCriteriaByScope($scope, 'testScope')->toArray()
        );
    }

    public function testGetCriteriaByScopeWithContext(): void
    {
        $scope = new StubScope();
        $scope->setScopeField('scope_value');

        $contextFieldValue = new \stdClass();
        $context = [
            'scopeField' => $contextFieldValue
        ];

        $this->scopeClassMetadata->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn([]);

        $provider = new StubScopeCriteriaProvider('scopeField', new \stdClass(), \stdClass::class);

        $manager = $this->getScopeManager(['testScope' => [$provider]]);
        $criteriaProperties = $manager->getCriteriaByScope($scope, 'testScope', $context)->toArray();
        $this->assertCount(1, $criteriaProperties);
        $this->assertArrayHasKey($provider->getCriteriaField(), $criteriaProperties);
        $this->assertSame($contextFieldValue, $criteriaProperties[$provider->getCriteriaField()]);
    }

    public function testFind(): void
    {
        $scope = new Scope();
        $fieldValue = new \stdClass();
        $scopeCriteria = new ScopeCriteria(
            ['fieldName' => $fieldValue, 'fieldName2' => null],
            $this->classMetadataFactory
        );

        $this->scopeClassMetadata->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(['fieldName', 'fieldName2']);

        $provider = new StubScopeCriteriaProvider('fieldName', $fieldValue, \stdClass::class);

        $this->scopeDataAccessor->expects($this->once())
            ->method('findOneByCriteria')
            ->with($scopeCriteria)
            ->willReturn($scope);

        $manager = $this->getScopeManager(['testScope' => [$provider]]);
        $this->assertEquals($scope, $manager->find('testScope'));
    }

    public function testFindWithArrayContext(): void
    {
        $scope = new Scope();
        $fieldValue = new \stdClass();
        $scopeCriteria = new ScopeCriteria(
            ['field' => $fieldValue, 'field2' => null],
            $this->classMetadataFactory
        );
        $context = ['field' => $fieldValue];

        $this->scopeClassMetadata->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(['field', 'field2']);

        $provider = new StubScopeCriteriaProvider('field', null, \stdClass::class);

        $this->scopeDataAccessor->expects($this->once())
            ->method('findOneByCriteria')
            ->with($scopeCriteria)
            ->willReturn($scope);

        $manager = $this->getScopeManager(['testScope' => [$provider]]);
        $this->assertEquals($scope, $manager->find('testScope', $context));
    }

    public function testFindWithObjectContext(): void
    {
        $scope = new Scope();
        $fieldValue = new \stdClass();
        $scopeCriteria = new ScopeCriteria(
            ['field' => $fieldValue, 'field2' => null],
            $this->classMetadataFactory
        );
        $context = new StubContext();
        $context->setField($fieldValue);

        $this->scopeClassMetadata->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(['field', 'field2']);

        $provider = new StubScopeCriteriaProvider('field', null, \stdClass::class);

        $this->scopeDataAccessor->expects($this->once())
            ->method('findOneByCriteria')
            ->with($scopeCriteria)
            ->willReturn($scope);

        $manager = $this->getScopeManager(['testScope' => [$provider]]);
        $this->assertEquals($scope, $manager->find('testScope', $context));
    }

    public function testFindWithIsNotNullValueInContext(): void
    {
        $scope = new Scope();
        $fieldValue = ScopeCriteria::IS_NOT_NULL;
        $scopeCriteria = new ScopeCriteria(
            ['field' => $fieldValue, 'field2' => null],
            $this->classMetadataFactory
        );
        $context = ['field' => $fieldValue];

        $this->scopeClassMetadata->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(['field', 'field2']);

        $provider = new StubScopeCriteriaProvider('field', null, \stdClass::class);

        $this->scopeDataAccessor->expects($this->once())
            ->method('findOneByCriteria')
            ->with($scopeCriteria)
            ->willReturn($scope);

        $manager = $this->getScopeManager(['testScope' => [$provider]]);
        $this->assertEquals($scope, $manager->find('testScope', $context));
    }

    public function testFindWithArrayValueInContext(): void
    {
        $scope = new Scope();
        $fieldValue = [1, 2, 3];
        $scopeCriteria = new ScopeCriteria(
            ['field' => $fieldValue, 'field2' => null],
            $this->classMetadataFactory
        );
        $context = ['field' => $fieldValue];

        $this->scopeClassMetadata->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(['field', 'field2']);

        $provider = new StubScopeCriteriaProvider('field', null, \stdClass::class);

        $this->scopeDataAccessor->expects($this->once())
            ->method('findOneByCriteria')
            ->with($scopeCriteria)
            ->willReturn($scope);

        $manager = $this->getScopeManager(['testScope' => [$provider]]);
        $this->assertEquals($scope, $manager->find('testScope', $context));
    }

    public function testFindWithInvalidScalarValueInContext(): void
    {
        $this->expectException(NotSupportedCriteriaValueException::class);
        $this->expectExceptionMessage(
            'The type string is not supported for context[field]. Expected stdClass, null, array or "IS_NOT_NULL".'
        );

        $context = ['field' => 'test'];

        $provider = new StubScopeCriteriaProvider('field', null, \stdClass::class);

        $this->scopeDataAccessor->expects($this->never())
            ->method('findOneByCriteria');

        $manager = $this->getScopeManager(['testScope' => [$provider]]);
        $manager->find('testScope', $context);
    }

    public function testFindWithInvalidObjectValueInContext(): void
    {
        $this->expectException(NotSupportedCriteriaValueException::class);
        $this->expectExceptionMessage(sprintf(
            'The type %s is not supported for context[field]. Expected stdClass, null, array or "IS_NOT_NULL".',
            StubScope::class
        ));

        $context = ['field' => new StubScope()];

        $provider = new StubScopeCriteriaProvider('field', null, \stdClass::class);

        $this->scopeDataAccessor->expects($this->never())
            ->method('findOneByCriteria');

        $manager = $this->getScopeManager(['testScope' => [$provider]]);
        $manager->find('testScope', $context);
    }

    public function testFindScheduled(): void
    {
        $scope = new Scope();
        $fieldValue = new \stdClass();
        $scopeCriteria = new ScopeCriteria(
            ['fieldName' => $fieldValue, 'fieldName2' => null],
            $this->classMetadataFactory
        );

        $this->scopeClassMetadata->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(['fieldName', 'fieldName2']);

        $provider = new StubScopeCriteriaProvider('fieldName', $fieldValue, \stdClass::class);

        $this->scopeDataAccessor->expects($this->once())
            ->method('findOneByCriteria')
            ->with($scopeCriteria);

        $this->scheduledForInsertScopes->expects($this->once())
            ->method('get')
            ->with($scopeCriteria)
            ->willReturn($scope);

        $manager = $this->getScopeManager(['testScope' => [$provider]]);
        $this->assertEquals($scope, $manager->find('testScope'));
    }

    public function testFindId(): void
    {
        $scopeId = 123;
        $fieldValue = new \stdClass();
        $scopeCriteria = new ScopeCriteria(
            ['field' => $fieldValue, 'field2' => null],
            $this->classMetadataFactory
        );

        $this->scopeClassMetadata->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(['field', 'field2']);

        $provider = new StubScopeCriteriaProvider('field', null, \stdClass::class);

        $this->scopeDataAccessor->expects($this->once())
            ->method('findIdentifierByCriteria')
            ->with($scopeCriteria)
            ->willReturn($scopeId);

        $manager = $this->getScopeManager(['testScope' => [$provider]]);
        $this->assertSame($scopeId, $manager->findId('testScope', ['field' => $fieldValue]));
    }

    public function testFindIdWhenScopeIdWasNotFound(): void
    {
        $this->scopeClassMetadata->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(['field']);

        $this->scopeDataAccessor->expects($this->once())
            ->method('findIdentifierByCriteria')
            ->with(new ScopeCriteria(['field' => null], $this->classMetadataFactory))
            ->willReturn(null);

        $manager = $this->getScopeManager(['testScope' => []]);
        $this->assertNull($manager->findId('testScope'));
    }

    public function testCreateScopeByCriteriaWithFlush(): void
    {
        $scopeCriteria = new ScopeCriteria([], $this->classMetadataFactory);

        $this->scheduledForInsertScopes->expects($this->once())
            ->method('get')
            ->with($scopeCriteria)
            ->willReturn(null);
        $this->scheduledForInsertScopes->expects($this->once())
            ->method('add')
            ->with($this->isInstanceOf(Scope::class), $this->identicalTo($scopeCriteria));
        $this->em->expects($this->once())
            ->method('flush');

        $manager = $this->getScopeManager();
        $this->assertInstanceOf(Scope::class, $manager->createScopeByCriteria($scopeCriteria));
    }

    public function testCreateScopeByCriteriaWithoutFlush(): void
    {
        $scopeCriteria = new ScopeCriteria([], $this->classMetadataFactory);

        $this->scheduledForInsertScopes->expects($this->once())
            ->method('get')
            ->with($scopeCriteria)
            ->willReturn(null);
        $this->scheduledForInsertScopes->expects($this->once())
            ->method('add')
            ->with($this->isInstanceOf(Scope::class), $this->identicalTo($scopeCriteria));
        $this->em->expects($this->never())
            ->method('flush');

        $manager = $this->getScopeManager();
        $this->assertInstanceOf(Scope::class, $manager->createScopeByCriteria($scopeCriteria, false));
    }

    public function testCreateScopeByCriteriaScheduled(): void
    {
        $scope = new Scope();
        $scopeCriteria = new ScopeCriteria([], $this->classMetadataFactory);

        $this->scheduledForInsertScopes->expects($this->once())
            ->method('get')
            ->with($scopeCriteria)
            ->willReturn($scope);
        $this->scheduledForInsertScopes->expects($this->never())
            ->method('add');
        $this->em->expects($this->never())
            ->method('flush');

        $manager = $this->getScopeManager();
        $this->assertEquals($scope, $manager->createScopeByCriteria($scopeCriteria));
    }

    public function testFindBy(): void
    {
        $scope = new Scope();
        $criteriaField = 'scopeField';
        $criteriaValue = new \stdClass();
        $scopeCriteria = new ScopeCriteria([$criteriaField => $criteriaValue], $this->classMetadataFactory);

        $this->scopeClassMetadata->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn([$criteriaField]);

        $provider = new StubScopeCriteriaProvider($criteriaField, $criteriaValue, \stdClass::class);

        $this->scopeDataAccessor->expects($this->once())
            ->method('findByCriteria')
            ->with($scopeCriteria)
            ->willReturn([$scope]);

        $manager = $this->getScopeManager(['testScope' => [$provider]]);
        $this->assertEquals([$scope], $manager->findBy('testScope'));
    }

    public function testFindRelatedScopes(): void
    {
        $scopes = [new Scope()];
        $scopeCriteria = new ScopeCriteria(
            ['fieldName' => ScopeCriteria::IS_NOT_NULL, 'fieldName2' => null],
            $this->classMetadataFactory
        );

        $this->scopeClassMetadata->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(['fieldName', 'fieldName2']);

        $provider = new StubScopeCriteriaProvider('fieldName', null, \stdClass::class);

        $this->scopeDataAccessor->expects($this->once())
            ->method('findByCriteria')
            ->with($scopeCriteria)
            ->willReturn($scopes);

        $manager = $this->getScopeManager(['testScope' => [$provider]]);
        $this->assertSame($scopes, $manager->findRelatedScopes('testScope'));
    }

    public function testFindRelatedScopeIds(): void
    {
        $scopeIds = [1, 4];
        $scopeCriteria = new ScopeCriteria(
            ['fieldName' => ScopeCriteria::IS_NOT_NULL, 'fieldName2' => null],
            $this->classMetadataFactory
        );

        $this->scopeClassMetadata->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(['fieldName', 'fieldName2']);

        $provider = new StubScopeCriteriaProvider('fieldName', null, \stdClass::class);

        $this->scopeDataAccessor->expects($this->once())
            ->method('findIdentifiersByCriteria')
            ->with($scopeCriteria)
            ->willReturn($scopeIds);

        $manager = $this->getScopeManager(['testScope' => [$provider]]);
        $this->assertSame($scopeIds, $manager->findRelatedScopeIds('testScope'));
    }

    public function testFindRelatedScopeIdsWithPriority(): void
    {
        $scopes = [new Scope()];
        $fieldValue = new \stdClass();
        $scopeCriteria = new ScopeCriteria(
            ['fieldName' => $fieldValue, 'fieldName2' => null],
            $this->classMetadataFactory
        );

        $this->scopeClassMetadata->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(['fieldName', 'fieldName2']);

        $provider = new StubScopeCriteriaProvider('fieldName', $fieldValue, \stdClass::class);

        $this->scopeDataAccessor->expects($this->once())
            ->method('findIdentifiersByCriteriaWithPriority')
            ->with($scopeCriteria)
            ->willReturn($scopes);

        $manager = $this->getScopeManager(['testScope' => [$provider]]);
        $this->assertSame($scopes, $manager->findRelatedScopeIdsWithPriority('testScope'));
    }

    public function testFindOrCreate(): void
    {
        $this->scopeClassMetadata->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn([]);

        $provider = new StubScopeCriteriaProvider('fieldName', null, \stdClass::class);

        $this->scopeDataAccessor->expects($this->once())
            ->method('findOneByCriteria')
            ->willReturn(null);

        $this->scheduledForInsertScopes->expects($this->once())
            ->method('get')
            ->with($this->isInstanceOf(ScopeCriteria::class))
            ->willReturn(null);
        $this->scheduledForInsertScopes->expects($this->once())
            ->method('add')
            ->with($this->isInstanceOf(Scope::class), $this->isInstanceOf(ScopeCriteria::class));
        $this->em->expects($this->once())
            ->method('flush');

        $manager = $this->getScopeManager(['testScope' => [$provider]]);
        $this->assertInstanceOf(Scope::class, $manager->findOrCreate('testScope'));
    }

    public function testFindOrCreateWithoutFlush(): void
    {
        $this->scopeClassMetadata->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn([]);

        $provider = new StubScopeCriteriaProvider('fieldName', null, \stdClass::class);

        $this->scopeDataAccessor->expects($this->once())
            ->method('findOneByCriteria')
            ->willReturn(null);

        $this->scheduledForInsertScopes->expects($this->once())
            ->method('get')
            ->with($this->isInstanceOf(ScopeCriteria::class))
            ->willReturn(null);
        $this->scheduledForInsertScopes->expects($this->once())
            ->method('add')
            ->with($this->isInstanceOf(Scope::class), $this->isInstanceOf(ScopeCriteria::class));
        $this->em->expects($this->never())
            ->method('flush');

        $manager = $this->getScopeManager(['testScope' => [$provider]]);
        $this->assertInstanceOf(Scope::class, $manager->findOrCreate('testScope', null, false));
    }

    public function testFindOrCreateUsingContext(): void
    {
        $context = ['scopeAttribute' => new \stdClass()];

        $this->scopeClassMetadata->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn([]);

        $provider = new StubScopeCriteriaProvider('fieldName', null, \stdClass::class);

        $this->scopeDataAccessor->expects($this->once())
            ->method('findOneByCriteria')
            ->willReturn(null);

        $this->scheduledForInsertScopes->expects($this->once())
            ->method('get')
            ->with($this->isInstanceOf(ScopeCriteria::class))
            ->willReturn(null);
        $this->scheduledForInsertScopes->expects($this->once())
            ->method('add')
            ->with($this->isInstanceOf(Scope::class), $this->isInstanceOf(ScopeCriteria::class));
        $this->em->expects($this->once())
            ->method('flush');

        $manager = $this->getScopeManager(['testScope' => [$provider]]);
        $this->assertInstanceOf(Scope::class, $manager->findOrCreate('testScope', $context));
    }

    public function testGetScopeEntities(): void
    {
        $provider = new StubScopeCriteriaProvider('scopeField', null, \stdClass::class);

        $manager = $this->getScopeManager(['testScope' => [$provider]]);
        $this->assertEquals(
            [
                $provider->getCriteriaField() => $provider->getCriteriaValueType()
            ],
            $manager->getScopeEntities('testScope')
        );
    }

    public function testFindMostSuitable(): void
    {
        $scope = new Scope();
        $fieldValue = new \stdClass();
        $scopeCriteria = new ScopeCriteria(
            ['fieldName' => $fieldValue, 'fieldName2' => null],
            $this->classMetadataFactory
        );

        $this->scopeClassMetadata->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(['fieldName', 'fieldName2']);

        $provider = new StubScopeCriteriaProvider('fieldName', $fieldValue, \stdClass::class);

        $this->scopeDataAccessor->expects($this->once())
            ->method('findMostSuitableByCriteria')
            ->with($scopeCriteria)
            ->willReturn($scope);

        $manager = $this->getScopeManager(['testScope' => [$provider]]);
        $this->assertEquals($scope, $manager->findMostSuitable('testScope'));
    }

    /**
     * @dataProvider isScopeMatchCriteriaDataProvider
     */
    public function testIsScopeMatchCriteria(
        bool $expectedResult,
        array $criteriaContext,
        string|object|null $scopeFieldValue
    ): void {
        $scope = new StubScope();
        $scope->setScopeField($scopeFieldValue);
        $scopeCriteria = new ScopeCriteria($criteriaContext, $this->classMetadataFactory);

        $this->scopeClassMetadata->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn([]);

        $provider = new StubScopeCriteriaProvider('scopeField', new \stdClass(), \stdClass::class);

        $manager = $this->getScopeManager(['testScope' => [$provider]]);
        $this->assertEquals(
            $expectedResult,
            $manager->isScopeMatchCriteria($scope, $scopeCriteria, 'testScope')
        );
    }

    public function isScopeMatchCriteriaDataProvider(): array
    {
        return [
            'scope match criteria'                             => [
                'expectedResult'  => true,
                'criteriaContext' => ['scopeField' => 'expected_value'],
                'scopeFieldValue' => 'expected_value'
            ],
            'scope dont match criteria'                        => [
                'expectedResult'  => false,
                'criteriaContext' => ['scopeField' => 'unexpected_value'],
                'scopeFieldValue' => 'expected_value'
            ],
            'scope match criteria with null value'             => [
                'expectedResult'  => true,
                'criteriaContext' => ['scopeField' => 'unexpected_value'],
                'scopeFieldValue' => null
            ],
            'scope dont match criteria with different objects' => [
                'expectedResult'  => false,
                'criteriaContext' => ['scopeField' => new \stdClass()],
                'scopeFieldValue' => new \stdClass()
            ]
        ];
    }
}
