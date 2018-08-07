<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Manager;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\ScopeBundle\Entity\Repository\ScopeRepository;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeCriteriaProviderInterface;
use Oro\Bundle\ScopeBundle\Manager\ScopeEntityStorage;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\ScopeBundle\Tests\Unit\Stub\StubScope;
use Oro\Bundle\ScopeBundle\Tests\Unit\Stub\StubScopeCriteriaProvider;
use Oro\Component\TestUtils\Mocks\ServiceLink;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class ScopeManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ScopeManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $manager;

    /**
     * @var ScopeEntityStorage|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityStorage;

    /**
     * @var EntityFieldProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityFieldProvider;

    public function setUp()
    {
        $this->entityStorage = $this->createMock(ScopeEntityStorage::class);
        $this->entityFieldProvider = $this->createMock(EntityFieldProvider::class);

        $serviceLink = new ServiceLink($this->entityFieldProvider);
        $this->manager = new ScopeManager($this->entityStorage, $serviceLink);
    }

    public function tearDown()
    {
        unset($this->manager, $this->registry, $this->entityFieldProvider);
    }

    public function testFindDefaultScope()
    {
        $this->entityFieldProvider->expects($this->once())
            ->method('getRelations')
            ->with(Scope::class, false, true, false)
            ->willReturn([['name' => 'relation']]);
        $expectedCriteria = new ScopeCriteria(['relation' => null], [['name' => 'relation']]);
        $repository = $this->getMockBuilder(ScopeRepository::class)->disableOriginalConstructor()->getMock();
        $scope = new Scope();
        $repository->method('findOneByCriteria')
            ->with($expectedCriteria)
            ->willReturn($scope);

        $this->entityStorage->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        $this->assertSame($scope, $this->manager->findDefaultScope());
    }

    public function testGetCriteriaByScope()
    {
        $this->manager->addProvider('test', new StubScopeCriteriaProvider());
        $scope = new StubScope();
        $scope->setScopeField('expected_value');
        $this->entityFieldProvider->method('getRelations')->willReturn([]);
        $this->assertSame(
            [StubScopeCriteriaProvider::STUB_FIELD => 'expected_value'],
            $this->manager->getCriteriaByScope($scope, 'test')->toArray()
        );
    }

    public function testFind()
    {
        $scope = new Scope();
        $provider = $this->createMock(ScopeCriteriaProviderInterface::class);
        $provider->method('getCriteriaForCurrentScope')->willReturn(['fieldName' => 1]);
        $fieldsInfo = [['name' => 'fieldName'], ['name' => 'fieldName2']];
        $scopeCriteria = new ScopeCriteria(['fieldName' => 1, 'fieldName2' => null], $fieldsInfo);
        $repository = $this->getMockBuilder(ScopeRepository::class)->disableOriginalConstructor()->getMock();
        $repository->method('findOneByCriteria')->with($scopeCriteria)->willReturn($scope);

        $this->entityStorage->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        $this->entityFieldProvider->method('getRelations')->willReturn(
            [
                ['name' => 'fieldName'],
                ['name' => 'fieldName2'],
            ]
        );

        $this->manager->addProvider('testScope', $provider);
        $actualScope = $this->manager->find('testScope');
        $this->assertEquals($scope, $actualScope);
    }

    public function testFindScheduled()
    {
        $scope = new Scope();
        $provider = $this->createMock(ScopeCriteriaProviderInterface::class);
        $provider->expects($this->any())
            ->method('getCriteriaForCurrentScope')
            ->willReturn(['fieldName' => 1]);

        $this->entityFieldProvider->expects($this->any())
            ->method('getRelations')
            ->willReturn([
                ['name' => 'fieldName'],
                ['name' => 'fieldName2'],
            ]);

        $fieldsInfo = [['name' => 'fieldName'], ['name' => 'fieldName2']];
        $scopeCriteria = new ScopeCriteria(['fieldName' => 1, 'fieldName2' => null], $fieldsInfo);
        $repository = $this->getMockBuilder(ScopeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->once())
            ->method('findOneByCriteria')
            ->with($scopeCriteria);

        $this->entityStorage->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        $this->entityStorage->expects($this->once())
            ->method('getScheduledForInsertByCriteria')
            ->with($scopeCriteria)
            ->willReturn($scope);

        $this->manager->addProvider('testScope', $provider);
        $actualScope = $this->manager->find('testScope');
        $this->assertEquals($scope, $actualScope);
    }

    public function testCreateScopeByCriteriaWithFlush()
    {
        $scopeCriteria = new ScopeCriteria([], []);
        $this->entityStorage->expects($this->once())
            ->method('getScheduledForInsertByCriteria')
            ->with($scopeCriteria)
            ->willReturn(null);
        $this->entityStorage->expects($this->once())
            ->method('scheduleForInsert')
            ->with($this->isInstanceOf(Scope::class), $scopeCriteria);
        $this->entityStorage->expects($this->once())
            ->method('flush');

        $this->assertInstanceOf(Scope::class, $this->manager->createScopeByCriteria($scopeCriteria));
    }

    public function testCreateScopeByCriteriaWithoutFlush()
    {
        $scopeCriteria = new ScopeCriteria([], []);
        $this->entityStorage->expects($this->once())
            ->method('getScheduledForInsertByCriteria')
            ->with($scopeCriteria)
            ->willReturn(null);
        $this->entityStorage->expects($this->once())
            ->method('scheduleForInsert')
            ->with($this->isInstanceOf(Scope::class), $scopeCriteria);
        $this->entityStorage->expects($this->never())
            ->method('flush');

        $this->assertInstanceOf(Scope::class, $this->manager->createScopeByCriteria($scopeCriteria, false));
    }

    public function testCreateScopeByCriteriaScheduled()
    {
        $scope = new Scope();
        $scopeCriteria = new ScopeCriteria([], []);
        $this->entityStorage->expects($this->once())
            ->method('getScheduledForInsertByCriteria')
            ->with($scopeCriteria)
            ->willReturn($scope);
        $this->entityStorage->expects($this->never())
            ->method('scheduleForInsert');
        $this->entityStorage->expects($this->never())
            ->method('flush');

        $this->assertEquals($scope, $this->manager->createScopeByCriteria($scopeCriteria));
    }

    public function testFindBy()
    {
        $scope = new Scope();
        $scopeCriteria = new ScopeCriteria(
            [StubScopeCriteriaProvider::STUB_FIELD => StubScopeCriteriaProvider::STUB_VALUE],
            [['name' => StubScopeCriteriaProvider::STUB_FIELD]]
        );
        $repository = $this->getMockBuilder(ScopeRepository::class)->disableOriginalConstructor()->getMock();
        $repository->method('findByCriteria')->with($scopeCriteria)->willReturn([$scope]);

        $this->entityStorage->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        $this->entityFieldProvider->method('getRelations')->willReturn(
            [
                ['name' => StubScopeCriteriaProvider::STUB_FIELD],
            ]
        );

        $this->manager->addProvider('testScope', new StubScopeCriteriaProvider());
        $this->assertEquals([$scope], $this->manager->findBy('testScope'));
    }

    public function testFindRelatedScopes()
    {
        $this->entityFieldProvider->method('getRelations')->willReturn(
            [
                ['name' => 'fieldName'],
                ['name' => 'fieldName2'],
            ]
        );
        /** @var ScopeCriteriaProviderInterface|\PHPUnit\Framework\MockObject\MockObject $provider */
        $provider = $this->createMock(ScopeCriteriaProviderInterface::class);
        $provider->method('getCriteriaByContext')
            ->willReturn([]);
        $provider->method('getCriteriaField')
            ->willReturn('fieldName');
        $this->manager->addProvider('test', $provider);
        $repository = $this->getMockBuilder(ScopeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $scopes = [new Scope()];
        $fieldsInfo = [['name' => 'fieldName'], ['name' => 'fieldName2']];
        $repository->expects($this->once())
            ->method('findByCriteria')
            ->with(new ScopeCriteria(['fieldName' => ScopeCriteria::IS_NOT_NULL, 'fieldName2' => null], $fieldsInfo))
            ->willReturn($scopes);

        $this->entityStorage->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        $this->assertSame($scopes, $this->manager->findRelatedScopes('test'));
    }

    public function testFindRelatedScopeIds()
    {
        $this->entityFieldProvider->method('getRelations')->willReturn(
            [
                ['name' => 'fieldName'],
                ['name' => 'fieldName2'],
            ]
        );
        /** @var ScopeCriteriaProviderInterface|\PHPUnit\Framework\MockObject\MockObject $provider */
        $provider = $this->createMock(ScopeCriteriaProviderInterface::class);
        $provider->method('getCriteriaByContext')
            ->willReturn([]);
        $provider->method('getCriteriaField')
            ->willReturn('fieldName');
        $this->manager->addProvider('test', $provider);
        $repository = $this->getMockBuilder(ScopeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $scopeIds = [1, 4];
        $fieldsInfo = [['name' => 'fieldName'], ['name' => 'fieldName2']];
        $repository->expects($this->once())
            ->method('findIdentifiersByCriteria')
            ->with(new ScopeCriteria(['fieldName' => ScopeCriteria::IS_NOT_NULL, 'fieldName2' => null], $fieldsInfo))
            ->willReturn($scopeIds);

        $this->entityStorage->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        $this->assertSame($scopeIds, $this->manager->findRelatedScopeIds('test'));
    }

    public function testFindRelatedScopeIdsWithPriority()
    {
        $this->entityFieldProvider->method('getRelations')->willReturn(
            [
                ['name' => 'fieldName'],
                ['name' => 'fieldName2'],
            ]
        );
        /** @var ScopeCriteriaProviderInterface|\PHPUnit\Framework\MockObject\MockObject $provider */
        $provider = $this->createMock(ScopeCriteriaProviderInterface::class);
        $provider->method('getCriteriaByContext')
            ->willReturn([]);
        $provider->method('getCriteriaForCurrentScope')->willReturn(['fieldName' => 1]);
        $this->manager->addProvider('test', $provider);
        $repository = $this->getMockBuilder(ScopeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $scopes = [new Scope()];
        $fieldsInfo = [['name' => 'fieldName'], ['name' => 'fieldName2']];
        $repository->expects($this->once())
            ->method('findIdentifiersByCriteriaWithPriority')
            ->with(new ScopeCriteria(['fieldName' => 1, 'fieldName2' => null], $fieldsInfo))
            ->willReturn($scopes);

        $this->entityStorage->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        $this->assertSame($scopes, $this->manager->findRelatedScopeIdsWithPriority('test'));
    }

    public function testFindOrCreate()
    {
        $scope = new Scope();
        $provider = $this->createMock(ScopeCriteriaProviderInterface::class);
        $provider->method('getCriteriaForCurrentScope')->willReturn([]);

        $repository = $this->getMockBuilder(ScopeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->method('findOneByCriteria')->willReturn(null);

        $this->entityStorage->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);
        $this->entityStorage->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);
        $this->entityStorage->expects($this->once())
            ->method('scheduleForInsert')
            ->with($scope, $this->isInstanceOf(ScopeCriteria::class));
        $this->entityStorage->expects($this->once())
            ->method('flush');

        $this->entityFieldProvider->method('getRelations')->willReturn([]);

        $this->manager->addProvider('testScope', $provider);
        $actualScope = $this->manager->findOrCreate('testScope');
        $this->assertEquals($scope, $actualScope);
    }

    public function testFindOrCreateWithoutFlush()
    {
        $scope = new Scope();
        $provider = $this->createMock(ScopeCriteriaProviderInterface::class);
        $provider->expects($this->atLeastOnce())
            ->method('getCriteriaForCurrentScope')->willReturn([]);

        $repository = $this->getMockBuilder(ScopeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('findOneByCriteria')
            ->willReturn(null);

        $this->entityStorage->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);
        $this->entityStorage->expects($this->once())
            ->method('scheduleForInsert')
            ->with($scope, $this->isInstanceOf(ScopeCriteria::class));
        $this->entityStorage->expects($this->never())
            ->method('flush');

        $this->entityFieldProvider->expects($this->once())
            ->method('getRelations')
            ->willReturn([]);

        $this->manager->addProvider('testScope', $provider);
        $actualScope = $this->manager->findOrCreate('testScope', null, false);
        $this->assertEquals($scope, $actualScope);
    }

    public function testFindOrCreateUsingContext()
    {
        $scope = new Scope();
        $context = ['scopeAttribute' => new \stdClass()];
        $provider = $this->createMock(ScopeCriteriaProviderInterface::class);
        $provider->method('getCriteriaByContext')->with($context)->willReturn([]);

        $repository = $this->getMockBuilder(ScopeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->method('findOneByCriteria')->willReturn(null);

        $this->entityStorage->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);
        $this->entityStorage->expects($this->once())
            ->method('scheduleForInsert')
            ->with($scope, $this->isInstanceOf(ScopeCriteria::class));
        $this->entityStorage->expects($this->once())
            ->method('flush');

        $this->entityFieldProvider->method('getRelations')->willReturn([]);

        $this->manager->addProvider('testScope', $provider);
        $actualScope = $this->manager->findOrCreate('testScope', $context);
        $this->assertEquals($scope, $actualScope);
    }

    public function testGetScopeEntities()
    {
        $this->manager->addProvider('scope_type', new StubScopeCriteriaProvider());
        $expected = [
            StubScopeCriteriaProvider::STUB_FIELD => StubScopeCriteriaProvider::STUB_CLASS
        ];

        $this->assertEquals($expected, $this->manager->getScopeEntities('scope_type'));
    }

    public function testFindMostSuitable()
    {
        $scope = new Scope();
        $provider = $this->createMock(ScopeCriteriaProviderInterface::class);
        $provider->expects($this->once())
            ->method('getCriteriaForCurrentScope')
            ->willReturn(['fieldName' => 1]);

        $fieldsInfo = [['name' => 'fieldName'], ['name' => 'fieldName2']];
        $scopeCriteria = new ScopeCriteria(['fieldName' => 1, 'fieldName2' => null], $fieldsInfo);
        $repository = $this->getMockBuilder(ScopeRepository::class)->disableOriginalConstructor()->getMock();
        $repository->expects($this->once())
            ->method('findMostSuitable')
            ->with($scopeCriteria)
            ->willReturn($scope);

        $this->entityStorage->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        $this->entityFieldProvider->expects($this->once())
            ->method('getRelations')
            ->willReturn([
                ['name' => 'fieldName'],
                ['name' => 'fieldName2'],
            ]);

        $this->manager->addProvider('testScope', $provider);
        $actualScope = $this->manager->findMostSuitable('testScope');
        $this->assertEquals($scope, $actualScope);
    }

    /**
     * @dataProvider isScopeMatchCriteriaDataProvider
     *
     * @param $expectedResult
     * @param $criteriaContext
     * @param $scopeFieldValue
     */
    public function testIsScopeMatchCriteria($expectedResult, $criteriaContext, $scopeFieldValue)
    {
        $criteria = new ScopeCriteria($criteriaContext, []);

        $this->manager->addProvider('some_type', new StubScopeCriteriaProvider());
        $scope = new StubScope();
        $scope->setScopeField($scopeFieldValue);
        $this->entityFieldProvider->method('getRelations')->willReturn([]);

        $result = $this->manager->isScopeMatchCriteria($scope, $criteria, 'some_type');
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function isScopeMatchCriteriaDataProvider()
    {
        return [
            'scope match criteria' => [
                'expectedResult' => true,
                'criteriaContext' => [StubScopeCriteriaProvider::STUB_FIELD => 'expected_value'],
                'scopeFieldValue' => 'expected_value'
            ],
            'scope dont match criteria' => [
                'expectedResult' => false,
                'criteriaContext' => [StubScopeCriteriaProvider::STUB_FIELD => 'unexpected_value'],
                'scopeFieldValue' => 'expected_value'
            ],
            'scope match criteria with null value' => [
                'expectedResult' => true,
                'criteriaContext' => [StubScopeCriteriaProvider::STUB_FIELD => 'unexpected_value'],
                'scopeFieldValue' => null
            ],
            'scope dont match criteria with different objects' => [
                'expectedResult' => false,
                'criteriaContext' => [StubScopeCriteriaProvider::STUB_FIELD => new \stdClass()],
                'scopeFieldValue' => new \stdClass()
            ],
        ];
    }
}
