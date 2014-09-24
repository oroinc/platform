<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Manager;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var Manager */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $builder;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configurationProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $parametersFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $nameStrategy;

    protected function setUp()
    {
        $this->configurationProvider =
            $this->getMockBuilder('Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface')
                ->disableOriginalConstructor()->getMock();

        $this->builder = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Builder')
            ->disableOriginalConstructor()->getMock();

        $this->parametersFactory =
            $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\RequestParameterBagFactory')
                ->disableOriginalConstructor()
                ->getMock();

        $this->nameStrategy =
            $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\NameStrategyInterface')
                ->disableOriginalConstructor()
                ->getMock();

        $this->manager = new Manager(
            $this->configurationProvider,
            $this->builder,
            $this->parametersFactory,
            $this->nameStrategy
        );
    }

    public function testGetDatagridByRequestParamsWorksWithoutScope()
    {
        $datagridName = 'test_grid';
        $additionalParameters = array('foo' => 'bar');

        $parameters = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\ParameterBag');
        $parameters->expects($this->once())->method('add')->with($additionalParameters);

        $this->nameStrategy->expects($this->atLeastOnce())
            ->method('parseGridScope')
            ->with($datagridName)
            ->will($this->returnValue(null));

        $this->nameStrategy->expects($this->atLeastOnce())
            ->method('parseGridName')
            ->with($datagridName)
            ->will($this->returnValue($datagridName));

        $this->parametersFactory->expects($this->once())
            ->method('createParameters')
            ->with($datagridName)
            ->will($this->returnValue($parameters));

        $configuration = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();

        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');

        $this->configurationProvider->expects($this->exactly(2))
            ->method('getConfiguration')
            ->with($datagridName)
            ->will($this->returnValue($configuration));

        $configuration->expects($this->once())
            ->method('offsetGetOr')
            ->with('scope')
            ->will($this->returnValue(null));

        $configuration->expects($this->never())
            ->method('offsetSet');

        $this->builder->expects($this->once())->method('build')
            ->with($configuration, $parameters)
            ->will($this->returnValue($datagrid));

        $this->assertEquals(
            $datagrid,
            $this->manager->getDatagridByRequestParams($datagridName, $additionalParameters)
        );
    }

    public function testGetDatagridByRequestParamsWorksWithScope()
    {
        $gridFullName = 'test_grid:test_scope';
        $gridName = 'test_grid';
        $gridScope = 'test_scope';
        $additionalParameters = array('foo' => 'bar');

        $parameters = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\ParameterBag');
        $parameters->expects($this->once())->method('add')->with($additionalParameters);

        $this->nameStrategy->expects($this->atLeastOnce())
            ->method('parseGridScope')
            ->with($gridFullName)
            ->will($this->returnValue($gridScope));

        $this->nameStrategy->expects($this->atLeastOnce())
            ->method('parseGridName')
            ->with($gridFullName)
            ->will($this->returnValue($gridName));

        $this->parametersFactory->expects($this->once())
            ->method('createParameters')
            ->with($gridFullName)
            ->will($this->returnValue($parameters));

        $configuration = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();

        $configuration->expects($this->never())
            ->method('offsetGetOr');

        $configuration->expects($this->once())
            ->method('offsetSet')
            ->with('scope', $gridScope);

        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');

        $this->configurationProvider->expects($this->once())
            ->method('getConfiguration')
            ->with($gridName)
            ->will($this->returnValue($configuration));

        $this->builder->expects($this->once())->method('build')
            ->with($configuration, $parameters)
            ->will($this->returnValue($datagrid));

        $this->assertEquals(
            $datagrid,
            $this->manager->getDatagridByRequestParams($gridFullName, $additionalParameters)
        );
    }

    public function testGetDatagridWithoutScope()
    {
        $datagridName = 'test_grid';
        $parameters = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\ParameterBag');

        $this->nameStrategy->expects($this->once())
            ->method('parseGridScope')
            ->with($datagridName)
            ->will($this->returnValue(null));

        $this->nameStrategy->expects($this->once())
            ->method('parseGridName')
            ->with($datagridName)
            ->will($this->returnValue($datagridName));

        $configuration = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();

        $configuration->expects($this->never())
            ->method($this->anything());

        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');

        $this->configurationProvider->expects($this->once())
            ->method('getConfiguration')
            ->with($datagridName)
            ->will($this->returnValue($configuration));

        $this->builder->expects($this->once())->method('build')
            ->with($configuration, $parameters)
            ->will($this->returnValue($datagrid));

        $this->assertEquals(
            $datagrid,
            $this->manager->getDatagrid($datagridName, $parameters)
        );
    }

    public function testGetDatagridWithScope()
    {
        $gridFullName = 'test_grid:test_scope';
        $gridName = 'test_grid';
        $gridScope = 'test_scope';
        $parameters = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\ParameterBag');

        $this->nameStrategy->expects($this->once())
            ->method('parseGridScope')
            ->with($gridFullName)
            ->will($this->returnValue($gridScope));

        $this->nameStrategy->expects($this->once())
            ->method('parseGridName')
            ->with($gridFullName)
            ->will($this->returnValue($gridName));

        $configuration = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();

        $configuration->expects($this->never())
            ->method('offsetGetOr');

        $configuration->expects($this->once())
            ->method('offsetSet')
            ->with('scope', $gridScope);

        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');

        $this->configurationProvider->expects($this->once())
            ->method('getConfiguration')
            ->with($gridName)
            ->will($this->returnValue($configuration));

        $this->builder->expects($this->once())->method('build')
            ->with($configuration, $parameters)
            ->will($this->returnValue($datagrid));

        $this->assertEquals(
            $datagrid,
            $this->manager->getDatagrid($gridFullName, $parameters)
        );
    }

    public function testGetDatagridWithDefaultParameters()
    {
        $datagridName = 'test_grid';

        $this->nameStrategy->expects($this->atLeastOnce())
            ->method('parseGridScope')
            ->with($datagridName)
            ->will($this->returnValue(null));

        $this->nameStrategy->expects($this->atLeastOnce())
            ->method('parseGridName')
            ->with($datagridName)
            ->will($this->returnValue($datagridName));

        $configuration = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();

        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');

        $this->configurationProvider->expects($this->once())
            ->method('getConfiguration')
            ->with($datagridName)
            ->will($this->returnValue($configuration));

        $this->builder->expects($this->once())->method('build')
            ->with(
                $configuration,
                $this->callback(
                    function ($parameters) {
                        $this->assertInstanceOf('Oro\Bundle\DataGridBundle\Datagrid\ParameterBag', $parameters);
                        $this->assertEquals(array(), $parameters->all());

                        return true;
                    }
                )
            )
            ->will($this->returnValue($datagrid));

        $this->assertEquals(
            $datagrid,
            $this->manager->getDatagrid($datagridName)
        );
    }

    public function testGetDatagridWithArrayParameters()
    {
        $datagridName = 'test_grid';
        $parameters = array('foo' => 'bar');

        $this->nameStrategy->expects($this->atLeastOnce())
            ->method('parseGridScope')
            ->with($datagridName)
            ->will($this->returnValue(null));

        $this->nameStrategy->expects($this->atLeastOnce())
            ->method('parseGridName')
            ->with($datagridName)
            ->will($this->returnValue($datagridName));

        $configuration = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();

        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');

        $this->configurationProvider->expects($this->once())
            ->method('getConfiguration')
            ->with($datagridName)
            ->will($this->returnValue($configuration));

        $this->builder->expects($this->once())->method('build')
            ->with(
                $configuration,
                $this->callback(
                    function ($value) use ($parameters) {
                        $this->assertInstanceOf('Oro\Bundle\DataGridBundle\Datagrid\ParameterBag', $value);
                        $this->assertEquals($parameters, $value->all());

                        return true;
                    }
                )
            )
            ->will($this->returnValue($datagrid));

        $this->assertEquals(
            $datagrid,
            $this->manager->getDatagrid($datagridName, $parameters)
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $parameters must be an array or instance of ParameterBag.
     */
    public function testGetDatagridThrowsException()
    {
        $datagridName = 'test_grid';
        $parameters = new \stdClass();

        $this->manager->getDatagrid($datagridName, $parameters);
    }
}
