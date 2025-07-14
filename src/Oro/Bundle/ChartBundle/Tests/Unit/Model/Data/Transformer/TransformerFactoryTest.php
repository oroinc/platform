<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Model\Data\Transformer;

use Oro\Bundle\ChartBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ChartBundle\Model\Data\Transformer\TransformerFactory;
use Oro\Bundle\ChartBundle\Model\Data\Transformer\TransformerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TransformerFactoryTest extends TestCase
{
    private ContainerInterface&MockObject $container;
    private TransformerFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);

        $this->factory = new TransformerFactory($this->container);
    }

    public function testCreateTransformer(): void
    {
        $expected = $this->createMock(TransformerInterface::class);

        $serviceId = 'transformer_service';
        $this->container->expects($this->once())
            ->method('get')
            ->with($serviceId)
            ->willReturn($expected);

        $this->assertEquals($expected, $this->factory->createTransformer($serviceId));
    }

    public function testCreateTransformerFails(): void
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
