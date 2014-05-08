<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Manager;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    const TEST_NAME = 'testGrid';

    /** @var Manager */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $builder;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configurationProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $parametersFactory;

    public function setUp()
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

        $this->manager = new Manager($this->configurationProvider, $this->builder, $this->parametersFactory);
    }

    public function testGetDatagridByRequestParams()
    {
        $datagridName = 'test_grid';
        $additionalParameters = array('foo' => 'bar');

        $parameters = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\ParameterBag');
        $parameters->expects($this->once())->method('add')->with($additionalParameters);

        $this->parametersFactory->expects($this->once())
            ->method('createParameters')
            ->with($datagridName)
            ->will($this->returnValue($parameters));

        $configuration = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();

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
            $this->manager->getDatagridByRequestParams($datagridName, $additionalParameters)
        );
    }

    public function testGetDatagrid()
    {
        $datagridName = 'test_grid';
        $parameters = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\ParameterBag');

        $configuration = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();

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

    public function testGetDatagridWithDefaultParameters()
    {
        $datagridName = 'test_grid';

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
