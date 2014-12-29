<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datagrid\RequestParameterBagFactory;

class RequestParameterBagFactoryTest extends \PHPUnit_Framework_TestCase
{
    const TEST_NAME = 'testGrid';

    const PARAMETERS_CLASS = 'Oro\Bundle\DataGridBundle\Datagrid\ParameterBag';

    /** @var RequestParameterBagFactory */
    protected $factory;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    protected function setUp()
    {
        $this->request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $this->factory = new RequestParameterBagFactory(self::PARAMETERS_CLASS);
        $this->factory->setRequest($this->request);
    }

    public function testCreateParameters()
    {
        $gridName       = 'test_grid';
        $gridParameters = array('foo' => 'bar');

        $this->request->expects($this->at(0))
            ->method('get')
            ->with($gridName, [], false)
            ->will($this->returnValue($gridParameters));
        $this->request->expects($this->at(1))
            ->method('get')
            ->with(RequestParameterBagFactory::DEFAULT_ROOT_PARAM, [], false)
            ->will($this->returnValue(array()));

        $parameters = $this->factory->createParameters($gridName);

        $this->assertInstanceOf(self::PARAMETERS_CLASS, $parameters);
        $this->assertEquals($gridParameters, $parameters->all());
    }

    public function testCreateParametersWithMinifiedData()
    {
        $gridName    = 'test_grid';
        $minifiedKey = 'f';
        $minifiedVal = 'value';

        $gridParameters     = array('foo' => 'bar');
        $minifiedParameters = array($gridName => $minifiedKey . '=' . $minifiedVal);

        $this->request->expects($this->at(0))
            ->method('get')
            ->with($gridName, [], false)
            ->will($this->returnValue($gridParameters));
        $this->request->expects($this->at(1))
            ->method('get')
            ->with(RequestParameterBagFactory::DEFAULT_ROOT_PARAM, [], false)
            ->will($this->returnValue($minifiedParameters));

        $parameters = $this->factory->createParameters($gridName);

        $expectedParameters = $gridParameters;
        $expectedParameters[ParameterBag::MINIFIED_PARAMETERS] = array($minifiedKey => $minifiedVal);

        $this->assertInstanceOf(self::PARAMETERS_CLASS, $parameters);
        $this->assertEquals($expectedParameters, $parameters->all());
    }


    public function testCreateParametersFromNotArrayRequestParams()
    {
        $gridName = 'test_grid';

        $this->request->expects($this->at(0))
            ->method('get')
            ->with($gridName, [], false)
            ->will($this->returnValue('foo'));
        $this->request->expects($this->at(1))
            ->method('get')
            ->with(RequestParameterBagFactory::DEFAULT_ROOT_PARAM, [], false)
            ->will($this->returnValue(null));

        $parameters = $this->factory->createParameters($gridName);

        $this->assertInstanceOf(self::PARAMETERS_CLASS, $parameters);
        $this->assertEquals(array(), $parameters->all());
    }
}
