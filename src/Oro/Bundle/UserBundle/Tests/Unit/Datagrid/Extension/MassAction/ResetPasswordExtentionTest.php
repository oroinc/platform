<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

use Oro\Bundle\UserBundle\Datagrid\Extension\MassAction\ResetPasswordExtension;

class ResetPasswordExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var  \PHPUnit_Framework_MockObject_MockObject|DatagridConfiguration */
    protected $configuration;

    /** @var  ResetPasswordExtension */
    protected $resetExtension;

    protected function setUp()
    {
        $this->configuration = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();

        $this->resetExtension = new ResetPasswordExtension();
    }

    public function testIsApplicable()
    {
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $request
            ->expects($this->once())
            ->method('get')
            ->with('actionName', false)
            ->will($this->returnValue('reset_password'));

        $requestStack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')
            ->disableOriginalConstructor()
            ->getMock();
        $requestStack
            ->expects($this->any())
            ->method('getCurrentRequest')
            ->will($this->returnValue($request));

        $this->configuration
            ->expects($this->once())
            ->method('offsetGetOr')
            ->with('name', null)
            ->will($this->returnValue(ResetPasswordExtension::USERS_GRID_NAME));

        $this->resetExtension->setRequestStack($requestStack);
        $this->assertTrue($this->resetExtension->isApplicable($this->configuration));
    }

    public function testVisitDatasource()
    {
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb
            ->expects($this->once())
            ->method('select')
            ->with('u');

        $dataSource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
        $dataSource
            ->expects($this->once())
            ->method('getQueryBuilder')
            ->will($this->returnValue($qb));
        $dataSource
            ->expects($this->once())
            ->method('setQueryBuilder');

        $this->resetExtension->visitDatasource($this->configuration, $dataSource);
    }
}
