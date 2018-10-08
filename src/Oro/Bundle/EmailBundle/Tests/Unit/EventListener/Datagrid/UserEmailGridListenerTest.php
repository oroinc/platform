<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener\Datagrid;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\EmailBundle\Datagrid\EmailQueryFactory;
use Oro\Bundle\EmailBundle\EventListener\Datagrid\UserEmailGridListener;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

class UserEmailGridListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var UserEmailGridListener */
    protected $listener;

    /** @var EmailQueryFactory|\PHPUnit\Framework\MockObject\MockObject */
    protected $queryFactory;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    protected $featureChecker;

    /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $datagrid;

    public function setUp()
    {
        $this->queryFactory = $this->createMock(EmailQueryFactory::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->listener = new UserEmailGridListener($this->queryFactory);
        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('test-email-feature');

        $this->datagrid = $this->createMock(DatagridInterface::class);
    }

    public function testOnBuildAfterWithDisabledFeature()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('test-email-feature', null)
            ->willReturn(false);
        $this->datagrid->expects($this->never())->method('getDatasource');

        $this->listener->onBuildAfter(new BuildAfter($this->datagrid));
    }

    public function testOnBuildAfter()
    {
        $this->featureChecker->expects($this->once())->method('isFeatureEnabled')->willReturn(true);
        $dataSource = $this->createMock(OrmDatasource::class);
        $this->datagrid->expects($this->once())->method('getDatasource')->willReturn($dataSource);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $countQueryBuilder = $this->createMock(QueryBuilder::class);
        $dataSource->expects($this->once())->method('getQueryBuilder')->willReturn($queryBuilder);
        $dataSource->expects($this->once())->method('getCountQb')->willReturn($countQueryBuilder);
        $this->queryFactory->expects($this->exactly(2))->method('applyAcl')->withConsecutive(
            [$queryBuilder],
            [$countQueryBuilder]
        );

        $this->listener->onBuildAfter(new BuildAfter($this->datagrid));
    }
}
