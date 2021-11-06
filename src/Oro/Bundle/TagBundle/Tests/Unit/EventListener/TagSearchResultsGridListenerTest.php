<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\TagBundle\EventListener\TagSearchResultsGridListener;
use Oro\Bundle\TagBundle\Security\SecurityProvider;

class TagSearchResultsGridListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ParameterBag|\PHPUnit\Framework\MockObject\MockObject */
    private $parameters;

    /** @var SecurityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $securityProvider;

    /** @var BuildAfter|\PHPUnit\Framework\MockObject\MockObject */
    private $event;

    /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $datagrid;

    /** @var EntityAliasResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityAliasResolver;

    /** @var TagSearchResultsGridListener */
    private $listener;

    protected function setUp(): void
    {
        $this->parameters = $this->createMock(ParameterBag::class);
        $this->securityProvider = $this->createMock(SecurityProvider::class);
        $this->datagrid = $this->createMock(DatagridInterface::class);
        $this->event = $this->createMock(BuildAfter::class);
        $this->entityAliasResolver = $this->createMock(EntityAliasResolver::class);

        $this->datagrid->expects($this->any())
            ->method('getParameters')
            ->willReturn($this->parameters);

        $this->listener = new TagSearchResultsGridListener($this->securityProvider, $this->entityAliasResolver);
    }

    /**
     * @dataProvider onBuildAfterDataProvider
     */
    public function testOnBuildAfter(
        string $alias,
        string $entityClass = null,
        EntityAliasNotFoundException $exception = null
    ) {
        $this->event->expects($this->once())
            ->method('getDatagrid')
            ->willReturn($this->datagrid);

        $qb = $this->createMock(QueryBuilder::class);
        $datasource = $this->createMock(OrmDatasource::class);
        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);
        $this->datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($datasource);
        $this->securityProvider->expects($this->once())
            ->method('applyAcl')
            ->with($qb, 'tt');
        $tagId = 100;

        $this->parameters->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['tag_id', 0],
                ['from', '']
            )
            ->willReturnOnConsecutiveCalls(
                $tagId,
                $alias
            );

        $setParameters = [
            ['tag', $tagId]
        ];

        if ($alias) {
            $earMockBuilder = $this->entityAliasResolver->expects($this->once())
                ->method('getClassByAlias')
                ->with($alias);
            if (null !== $entityClass) {
                $earMockBuilder->willReturn($entityClass);
                $qb->expects($this->once())
                    ->method('andWhere')
                    ->with('tt.entityName = :entityClass')
                    ->willReturnSelf();
                $setParameters[] = ['entityClass', $entityClass];
            } else {
                $earMockBuilder->willThrowException($exception);
                $qb->expects($this->once())
                    ->method('andWhere')
                    ->with('1 = 0')
                    ->willReturnSelf();
            }
        }

        $qb->expects($this->exactly(count($setParameters)))
            ->method('setParameter')
            ->withConsecutive(...$setParameters)
            ->willReturnSelf();

        $this->listener->onBuildAfter($this->event);
    }

    public function onBuildAfterDataProvider(): array
    {
        return [
            'empty parameter' => [''],
            'existing alias' => ['testalias', 'testEntity'],
            'not existing alias' => ['testalias', null, new EntityAliasNotFoundException()]
        ];
    }
}
