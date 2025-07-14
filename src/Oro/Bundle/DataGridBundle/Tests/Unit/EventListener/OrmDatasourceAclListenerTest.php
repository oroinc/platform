<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\EventListener;

use Doctrine\ORM\AbstractQuery;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\OrmResultBefore;
use Oro\Bundle\DataGridBundle\EventListener\OrmDatasourceAclListener;
use Oro\Bundle\SecurityBundle\AccessRule\AclAccessRule;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use PHPUnit\Framework\TestCase;

class OrmDatasourceAclListenerTest extends TestCase
{
    public function testOnResultBeforeWithDisabledAclApplyInConfig(): void
    {
        $aclHelper = $this->createMock(AclHelper::class);
        $datagridConfig = $this->createMock(DatagridConfiguration::class);
        $datagrid = $this->createMock(DatagridInterface::class);
        $query = $this->createMock(AbstractQuery::class);

        $datagrid->expects(self::once())
            ->method('getConfig')
            ->willReturn($datagridConfig);

        $datagridConfig->expects(self::once())
            ->method('isDatasourceSkipAclApply')
            ->willReturn(true);
        $datagridConfig->expects(self::never())
            ->method('getDatasourceAclApplyPermission');
        $aclHelper->expects(self::never())
            ->method('apply');

        $event = new OrmResultBefore($datagrid, $query);
        $listener = new OrmDatasourceAclListener($aclHelper);

        $listener->onResultBefore($event);
    }

    public function testOnResultBeforeWithEnabledAclApplyInConfig(): void
    {
        $aclHelper = $this->createMock(AclHelper::class);
        $datagridConfig = $this->createMock(DatagridConfiguration::class);
        $datagrid = $this->createMock(DatagridInterface::class);
        $query = $this->createMock(AbstractQuery::class);

        $datagrid->expects(self::once())
            ->method('getConfig')
            ->willReturn($datagridConfig);

        $datagridConfig->expects(self::once())
            ->method('isDatasourceSkipAclApply')
            ->willReturn(false);
        $datagridConfig->expects(self::once())
            ->method('getDatasourceAclApplyPermission')
            ->willReturn('EDIT');
        $datagridConfig->expects(self::once())
            ->method('offsetGetByPath')
            ->with(DatagridConfiguration::ACL_CONDITION_DATA_BUILDER_CONTEXT)
            ->willReturn(['test' => 23]);
        $aclHelper->expects(self::once())
            ->method('apply')
            ->with($query, 'EDIT', [AclAccessRule::CONDITION_DATA_BUILDER_CONTEXT => ['test' => 23]])
            ->willReturn($query);

        $event = new OrmResultBefore($datagrid, $query);
        $listener = new OrmDatasourceAclListener($aclHelper);

        $listener->onResultBefore($event);
    }
}
