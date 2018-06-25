<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Model\Data\Transformer;

use Oro\Bundle\ChartBundle\Model\Data\Transformer\TransformerFactory;

class TransformerFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $container;

    /**
     * @var TransformerFactory
     */
    protected $factory;

    protected function setUp()
    {
        $this->container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->factory = new TransformerFactory($this->container);
    }

    public function testCreateTransformer()
    {
        $expected = $this->createMock('Oro\Bundle\ChartBundle\Model\Data\Transformer\TransformerInterface');

        $serviceId = 'transformer_service';
        $this->container->expects($this->once())
            ->method('get')
            ->with($serviceId)
            ->will($this->returnValue($expected));

        $this->assertEquals($expected, $this->factory->createTransformer($serviceId));
    }

    /**
     * @expectedException \Oro\Bundle\ChartBundle\Exception\InvalidArgumentException
     * @expectedMessage Service "transformer_service" must be an instance of
     * "Oro\Bundle\ChartBundle\Model\Data\Transformer\TransformerInterface".
     */
    public function testCreateTransformerFails()
    {
        $serviceId = 'transformer_service';
        $this->container->expects($this->once())
            ->method('get')
            ->with($serviceId)
            ->will($this->returnValue(new \stdClass()));

        $this->factory->createTransformer($serviceId);
    }
}
