<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Datagrid\Extension\MassAction;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\UserBundle\Datagrid\Extension\MassAction\ResetPasswordExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ResetPasswordExtentionTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DatagridConfiguration */
    private $configuration;

    /** @var ResetPasswordExtension */
    private $resetExtension;

    protected function setUp(): void
    {
        $this->configuration = $this->createMock(DatagridConfiguration::class);

        $this->resetExtension = new ResetPasswordExtension();
        $this->resetExtension->setParameters(new ParameterBag());
    }

    public function testIsApplicable()
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('get')
            ->with('actionName', false)
            ->willReturn('reset_password');

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->configuration->expects($this->once())
            ->method('offsetGetOr')
            ->with('name', null)
            ->willReturn(ResetPasswordExtension::USERS_GRID_NAME);

        $this->resetExtension->setRequestStack($requestStack);
        $this->assertTrue($this->resetExtension->isApplicable($this->configuration));
    }

    public function testVisitDatasource()
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('select')
            ->with('u');

        $dataSource = $this->createMock(OrmDatasource::class);
        $dataSource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);
        $dataSource->expects($this->once())
            ->method('setQueryBuilder');

        $this->resetExtension->visitDatasource($this->configuration, $dataSource);
    }
}
