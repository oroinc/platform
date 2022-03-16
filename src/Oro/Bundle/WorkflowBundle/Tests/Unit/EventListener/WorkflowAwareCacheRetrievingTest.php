<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowAwareCache;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class WorkflowAwareCacheRetrievingTest extends \PHPUnit\Framework\TestCase
{
    /** @var CacheInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var WorkflowAwareCache */
    private $workflowAwareCache;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->workflowAwareCache = new WorkflowAwareCache($this->cache, $this->doctrineHelper);
    }

    /**
     * @dataProvider listDataProvider
     */
    public function testHasRelatedActiveWorkflowsFetched(bool $expected, array $classes, object $entity)
    {
        $this->assertCacheFetching(
            'active_workflow_related',
            $entity,
            $classes
        );

        $this->assertEquals($expected, $this->workflowAwareCache->hasRelatedActiveWorkflows($entity));
    }

    /**
     * @dataProvider listDataProvider
     */
    public function testHasRelatedActiveWorkflowsFetchingAndSave(bool $expected, array $classes, object $entity)
    {
        $this->assertRepositoryFetching(
            'active_workflow_related',
            true,
            $entity,
            $classes
        );

        $this->assertEquals($expected, $this->workflowAwareCache->hasRelatedActiveWorkflows($entity));
    }

    /**
     * @dataProvider listDataProvider
     */
    public function testHasRelatedWorkflowsFetched(bool $expected, array $classes, object $entity)
    {
        $this->assertCacheFetching(
            'all_workflow_related',
            $entity,
            $classes
        );

        $this->assertEquals($expected, $this->workflowAwareCache->hasRelatedWorkflows($entity));
    }

    /**
     * @dataProvider listDataProvider
     */
    public function testHasRelatedWorkflowsFetchingAndSave(bool $expected, array $classes, object $entity)
    {
        $this->assertRepositoryFetching(
            'all_workflow_related',
            false,
            $entity,
            $classes
        );

        $this->assertEquals($expected, $this->workflowAwareCache->hasRelatedWorkflows($entity));
    }

    public function listDataProvider(): array
    {
        return [
            'nope' => [
                'expected' => false,
                'classes' => [\DateTime::class => 0, \DateInterval::class => 1],
                'entity' => new \stdClass()
            ],
            'no. FQCNs must be keys. Not values.' => [
                'expected' => false,
                'classes' => [\stdClass::class, \DateTime::class],
                'entity' => new \stdClass()
            ],
            'yes' => [
                'expected' => true,
                'classes' => [\stdClass::class => 0, \DateTime::class => 1],
                'entity' => new \stdClass()
            ],
            'nope cause empty' => [
                'expected' => false,
                'classes' => [],
                'entity' => new \stdClass()
            ]
        ];
    }

    private function assertRepositoryFetching(
        string $cacheKey,
        bool $onlyActiveWorkflows,
        object $entity,
        array $classes
    ): void {
        $dbResult = array_flip($classes);

        $repository = $this->createMock(WorkflowDefinitionRepository::class);
        $repository->expects($this->once())
            ->method('getAllRelatedEntityClasses')
            ->with($onlyActiveWorkflows)
            ->willReturn($dbResult);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->willReturn(get_class($entity));
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(WorkflowDefinition::class)
            ->willReturn($repository);

        $this->cache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });
    }

    private function assertCacheFetching(string $cacheKey, object $entity, array $classes): void
    {
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->willReturn(get_class($entity));

        $this->cache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn($classes);
    }
}
