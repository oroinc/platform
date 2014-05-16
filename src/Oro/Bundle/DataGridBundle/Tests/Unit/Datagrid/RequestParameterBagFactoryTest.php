<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid;

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
        $gridName = 'test_grid';

        $gridParameters = array('foo' => 'bar');

        $this->request->expects($this->once())
            ->method('get')
            ->with($gridName, [], false)
            ->will($this->returnValue($gridParameters));

        $parameters = $this->factory->createParameters($gridName);

        $this->assertInstanceOf(self::PARAMETERS_CLASS, $parameters);

        $this->assertEquals($gridParameters, $parameters->all());
    }

    public function testCreateParametersFromNotArrayRequestParams()
    {
        $gridName = 'test_grid';

        $this->request->expects($this->once())
            ->method('get')
            ->with($gridName, [], false)
            ->will($this->returnValue('foo'));

        $parameters = $this->factory->createParameters($gridName);

        $this->assertInstanceOf(self::PARAMETERS_CLASS, $parameters);

        $this->assertEquals(array(), $parameters->all());
    }
}
