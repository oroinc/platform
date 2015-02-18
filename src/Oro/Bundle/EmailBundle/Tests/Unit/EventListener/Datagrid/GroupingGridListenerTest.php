<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener\Datagrid;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EmailBundle\EventListener\Datagrid\GroupingGridListener;

class GroupingGridListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var GroupingGridListener
     */
    protected $groupingGridListener;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var DatagridInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $datagrid;

    /**
     * @var DatagridConfiguration|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $config;

    /**
     * @var OrmDatasource|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $ormDatasource;

    /**
     * @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $queryBuilder;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $this->config = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();

        $this->ormDatasource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();

        $this->queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->datagrid->expects($this->any())
            ->method('getDatasource')
            ->willReturn($this->ormDatasource);

        $this->ormDatasource->expects($this->any())
            ->method('getQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->groupingGridListener = new GroupingGridListener($this->configManager);
    }

    /**
     * @param $enabled
     * @param $arrayModify
     *
     * @dataProvider buildBeforeProvider
     */
    public function testOnBuildBefore($enabled, $arrayModify)
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_activity_list.grouping')
            ->willReturn($enabled);

        $this->config->expects($this->exactly($arrayModify))
            ->method('offsetAddToArray');

        $buildBeforeEvent = new BuildBefore($this->datagrid, $this->config);
        $this->groupingGridListener->onBuildBefore($buildBeforeEvent);
    }

    public function buildBeforeProvider()
    {
        return [
            'configEnabled' => [
                'enabled' => true,
                'arrayModify' => 1,
            ],
            'configDisabled' => [
                'enabled' => false,
                'arrayModify' => 0,
            ],
        ];
    }

    /**
     * @param $enabled
     * @param $andWhereCalls
     *
     * @dataProvider buildAfterProvider
     */
    public function testOnBuildAfter($enabled, $andWhereCalls)
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_activity_list.grouping')
            ->willReturn($enabled);

        $this->queryBuilder->expects($this->exactly($andWhereCalls))
            ->method('andWhere');

        $buildAfterEvent = new BuildAfter($this->datagrid);
        $this->groupingGridListener->onBuildAfter($buildAfterEvent);
    }

    public function buildAfterProvider()
    {
        return [
            'configEnabled' => [
                'enabled' => true,
                'andWhereCalls' => 1,
            ],
            'configDisabled' => [
                'enabled' => false,
                'andWhereCalls' => 0,
            ],
        ];
    }
}
