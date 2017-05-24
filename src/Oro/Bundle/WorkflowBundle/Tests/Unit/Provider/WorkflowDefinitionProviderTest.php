<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Provider;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Provider\WorkflowDefinitionProvider;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Stub\StubEntity;

class WorkflowDefinitionProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var CacheProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $cache;

    /** @var WorkflowDefinition|\PHPUnit_Framework_MockObject_MockObject */
    protected $definition;

    /** @var Registry|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var WorkflowDefinitionRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    /** @var WorkflowDefinitionProvider */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->cache = $this->createMock(CacheProvider::class);
        $this->doctrine = $this->createMock(Registry::class);
        $this->repository = $this->createMock(WorkflowDefinitionRepository::class);
        $this->provider = new WorkflowDefinitionProvider($this->doctrine, $this->cache);
        $this->definition = $this->createMock(WorkflowDefinition::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset(
            $this->cache,
            $this->doctrine,
            $this->repository,
            $this->provider,
            $this->definition
        );
    }

    public function testGetActiveDefinitions()
    {
        $this->configureORM('findActive', [$this->definition]);
        $this->configureCache([$this->definition]);

        $this->provider->getActiveDefinitions();
        //Call again to verify if cache called
        $this->provider->getActiveDefinitions();
    }

    public function testGetDefinitionsForRelatedEntity()
    {
        $this->configureORM('findForRelatedEntity', [$this->definition], StubEntity::class);
        $this->configureCache([$this->definition]);

        $this->provider->getDefinitionsForRelatedEntity(StubEntity::class);
        //Call again to verify if cache called
        $this->provider->getDefinitionsForRelatedEntity(StubEntity::class);
    }

    public function testGetActiveDefinitionsForRelatedEntity()
    {
        $this->configureORM('findActiveForRelatedEntity', [$this->definition], StubEntity::class);
        $this->configureCache([$this->definition]);

        $this->provider->getActiveDefinitionsForRelatedEntity(StubEntity::class);
        //Call again to verify if cache called
        $this->provider->getActiveDefinitionsForRelatedEntity(StubEntity::class);
    }

    public function testInvalidateCache()
    {
        $this->cache->expects($this->once())->method('deleteAll');
        $this->provider->invalidateCache();
    }

    /**
     * @param mixed $result
     */
    protected function configureCache($result)
    {
        $this->cache->expects($this->once())->method('save');
        $this->cache->expects($this->exactly(2))->method('fetch')->willReturnOnConsecutiveCalls(false, $result);
    }

    /**
     * @param string $repositoryMethod
     * @param mixed $repositoryResult
     * @param mixed|null $repositoryMethodWith
     */
    protected function configureORM($repositoryMethod, $repositoryResult, $repositoryMethodWith = null)
    {
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with(WorkflowDefinition::class)
            ->willReturn($this->repository);

        if ($repositoryMethodWith) {
            $this->repository->expects($this->once())
                ->method($repositoryMethod)
                ->with($repositoryMethodWith)
                ->willReturn($repositoryResult);
        } else {
            $this->repository->expects($this->once())
                ->method($repositoryMethod)
                ->willReturn($repositoryResult);
        }
    }
}
