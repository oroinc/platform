<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Model\Data\Transformer;

use Oro\Bundle\ChartBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ChartBundle\Model\Data\Transformer\TransformerFactory;
use Oro\Bundle\ChartBundle\Model\Data\Transformer\TransformerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TransformerFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    /** @var TransformerFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);

        $this->factory = new TransformerFactory($this->container);
    }

    public function testCreateTransformer()
    {
        $expected = $this->createMock(TransformerInterface::class);

        $serviceId = 'transformer_service';
        $this->container->expects($this->once())
            ->method('get')
            ->with($serviceId)
            ->willReturn($expected);

        $this->assertEquals($expected, $this->factory->createTransformer($serviceId));
    }

    public function testCreateTransformerFails()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Service "transformer_service" must be an instance of "%s".',
            TransformerInterface::class
        ));

        $serviceId = 'transformer_service';
        $this->container->expects($this->once())
            ->method('get')
            ->with($serviceId)
            ->willReturn(new \stdClass());

        $this->factory->createTransformer($serviceId);
    }
}
