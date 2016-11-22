<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\ScopeBundle\Entity\Repository\ScopeRepository;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeCriteriaProviderInterface;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\ScopeBundle\Tests\Unit\Stub\StubScope;
use Oro\Bundle\ScopeBundle\Tests\Unit\Stub\StubScopeCriteriaProvider;
use Oro\Component\TestUtils\Mocks\ServiceLink;

class ScopeManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ScopeManager
     */
    protected $manager;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var EntityFieldProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityFieldProvider;

    public function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->entityFieldProvider = $this->getMockBuilder(EntityFieldProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $serviceLink = new ServiceLink($this->entityFieldProvider);
        $this->manager = new ScopeManager($this->registry, $serviceLink);
    }

    public function tearDown()
    {
        unset($this->manager, $this->registry, $this->entityFieldProvider);
    }

    public function testFindDefaultScope()
    {
        $this->entityFieldProvider->expects($this->once())
            ->method('getRelations')
            ->with(Scope::class)
            ->willReturn([['name' => 'relation']]);
        $expectedCriteria = new ScopeCriteria(['relation' => null]);
        $repository = $this->getMockBuilder(ScopeRepository::class)->disableOriginalConstructor()->getMock();
        $scope = new Scope();
        $repository->method('findOneByCriteria')
            ->with($expectedCriteria)
            ->willReturn($scope);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);
        $this->registry->method('getManagerForClass')->willReturn($em);

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
        $provider = $this->getMock(ScopeCriteriaProviderInterface::class);
        $provider->method('getCriteriaForCurrentScope')->willReturn(['fieldName' => 1]);
        $scopeCriteria = new ScopeCriteria(['fieldName' => 1, 'fieldName2' => null]);
        $repository = $this->getMockBuilder(ScopeRepository::class)->disableOriginalConstructor()->getMock();
        $repository->method('findOneByCriteria')->with($scopeCriteria)->willReturn($scope);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);
        $this->registry->method('getManagerForClass')->willReturn($em);

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

    public function testFindBy()
    {
        $scope = new Scope();
        $scopeCriteria = new ScopeCriteria(
            [StubScopeCriteriaProvider::STUB_FIELD => StubScopeCriteriaProvider::STUB_VALUE]
        );
        $repository = $this->getMockBuilder(ScopeRepository::class)->disableOriginalConstructor()->getMock();
        $repository->method('findByCriteria')->with($scopeCriteria)->willReturn([$scope]);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);
        $this->registry->method('getManagerForClass')->willReturn($em);

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
        /** @var ScopeCriteriaProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMock(ScopeCriteriaProviderInterface::class);
        $provider->method('getCriteriaByContext')
            ->willReturn([]);
        $provider->method('getCriteriaField')
            ->willReturn('fieldName');
        $this->manager->addProvider('test', $provider);
        $repository = $this->getMockBuilder(ScopeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $scopes = [new Scope()];
        $repository->expects($this->once())
            ->method('findByCriteria')
            ->with(new ScopeCriteria(['fieldName' => ScopeCriteria::IS_NOT_NULL, 'fieldName2' => null]))
            ->willReturn($scopes);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $this->registry->method('getManagerForClass')->willReturn($em);
        $this->assertSame($scopes, $this->manager->findRelatedScopes('test'));
    }

    public function testFindOrCreate()
    {
        $scope = new Scope();
        $provider = $this->getMock(ScopeCriteriaProviderInterface::class);
        $provider->method('getCriteriaForCurrentScope')->willReturn([]);

        $repository = $this->getMockBuilder(ScopeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->method('findOneByCriteria')->willReturn(null);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);
        $em->expects($this->once())->method('persist')->with($scope);
        $em->expects($this->once())->method('flush')->with($scope);

        $this->registry->method('getManagerForClass')->willReturn($em);

        $this->entityFieldProvider->method('getRelations')->willReturn([]);

        $this->manager->addProvider('testScope', $provider);
        $actualScope = $this->manager->findOrCreate('testScope');
        $this->assertEquals($scope, $actualScope);
    }

    public function testFindOrCreateUsingContext()
    {
        $scope = new Scope();
        $context = ['scopeAttribute' => new \stdClass()];
        $provider = $this->getMock(ScopeCriteriaProviderInterface::class);
        $provider->method('getCriteriaByContext')->with($context)->willReturn([]);

        $repository = $this->getMockBuilder(ScopeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->method('findOneByCriteria')->willReturn(null);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);
        $em->expects($this->once())->method('persist')->with($scope);
        $em->expects($this->once())->method('flush')->with($scope);

        $this->registry->method('getManagerForClass')->willReturn($em);

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
}
