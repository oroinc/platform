<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowAwareCache;

class WorkflowAwareCacheRetrievingTest extends \PHPUnit\Framework\TestCase
{
    /** @var Cache|\PHPUnit\Framework\MockObject\MockObject */
    protected $cache;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var WorkflowAwareCache */
    protected $workflowAwareCache;

    protected function setUp()
    {
        $this->cache = $this->createMock(Cache::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->workflowAwareCache = new WorkflowAwareCache($this->cache, $this->doctrineHelper);
    }

    /**
     * @dataProvider theList
     *
     * @param boolean $expected
     * @param string[] $classes
     * @param object $entity
     */
    public function testHasRelatedActiveWorkflowsFetched($expected, array $classes, $entity)
    {
        $this->assertCacheFetching(
            $cacheKey = 'active_workflow_related',
            $entity,
            $classes
        );

        $this->assertEquals($expected, $this->workflowAwareCache->hasRelatedActiveWorkflows($entity));
    }

    /**
     * @dataProvider theList
     * @param boolean $expected
     * @param array $classes
     * @param object $entity
     */
    public function testHasRelatedActiveWorkflowsFetchingAndSave($expected, array $classes, $entity)
    {
        $this->assertRepositoryFetching(
            $cacheKey = 'active_workflow_related',
            $onlyActiveWorkflows = true,
            $entity,
            $classes
        );

        $this->assertEquals($expected, $this->workflowAwareCache->hasRelatedActiveWorkflows($entity));
    }

    /**
     * @dataProvider theList
     * @param boolean $expected
     * @param string[] $classes
     * @param object $entity
     */
    public function testHasRelatedWorkflowsFetched($expected, array $classes, $entity)
    {
        $this->assertCacheFetching(
            $cacheKey = 'all_workflow_related',
            $entity,
            $classes
        );

        $this->assertEquals($expected, $this->workflowAwareCache->hasRelatedWorkflows($entity));
    }

    /**
     * @dataProvider theList
     * @param boolean $expected
     * @param array $classes
     * @param object $entity
     */
    public function testHasRelatedWorkflowsFetchingAndSave($expected, array $classes, $entity)
    {
        $this->assertRepositoryFetching(
            $cacheKey = 'all_workflow_related',
            $onlyActiveWorkflows = false,
            $entity,
            $classes
        );

        $this->assertEquals($expected, $this->workflowAwareCache->hasRelatedWorkflows($entity));
    }

    /**
     * @return \Generator
     */
    public function theList()
    {
        yield 'nope' => [
            'expected' => false,
            'classes' => [\DateTime::class => 0, \DateInterval::class => 1],
            'entity' => new \stdClass()
        ];

        yield 'no. FQCNs must be keys. Not values.' => [
            'expected' => false,
            'classes' => [\stdClass::class, \DateTime::class],
            'entity' => new \stdClass()
        ];

        yield 'yes' => [
            'expected' => true,
            'classes' => [\stdClass::class => 0, \DateTime::class => 1],
            'entity' => new \stdClass()
        ];

        yield 'nope cause empty' => [
            'expected' => false,
            'classes' => [],
            'entity' => new \stdClass()
        ];
    }

    /**
     * @param $cacheKey
     * @param bool $onlyActiveWorkflows
     * @param object $entity
     * @param array $classes
     */
    protected function assertRepositoryFetching($cacheKey, $onlyActiveWorkflows, $entity, $classes)
    {
        $dbResult = array_flip($classes);

        $repository = $this->createMock(WorkflowDefinitionRepository::class);
        $repository->expects($this->once())
            ->method('getAllRelatedEntityClasses')
            ->with($onlyActiveWorkflows)
            ->willReturn($dbResult);

        $this->doctrineHelper->expects($this->once())->method('getEntityClass')->willReturn(get_class($entity));
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(WorkflowDefinition::class)
            ->willReturn($repository);

        $this->cache->expects($this->once())->method('fetch')->with($cacheKey)->willReturn(false);
        $this->cache->expects($this->once())->method('save')->with($cacheKey, $classes);
    }

    /**
     * @param string $cacheKey
     * @param object $entity
     * @param array $classes
     */
    protected function assertCacheFetching($cacheKey, $entity, array $classes)
    {
        $this->doctrineHelper->expects($this->once())->method('getEntityClass')->willReturn(get_class($entity));

        $this->cache->expects($this->once())->method('fetch')->with($cacheKey)->willReturn($classes);
    }
}
