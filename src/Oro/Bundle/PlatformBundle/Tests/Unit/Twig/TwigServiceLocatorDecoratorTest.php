<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Twig;

use Oro\Bundle\PlatformBundle\Twig\TwigServiceLocatorDecorator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class TwigServiceLocatorDecoratorTest extends TestCase
{
    private ContainerInterface&MockObject $innerServiceLocator;
    private TwigServiceLocatorDecorator $serviceLocatorDecorator;

    #[\Override]
    protected function setUp(): void
    {
        $this->innerServiceLocator = $this->createMock(ContainerInterface::class);

        $this->serviceLocatorDecorator = new TwigServiceLocatorDecorator($this->innerServiceLocator);
    }

    public function testGet(): void
    {
        $serviceId = 'test.service';
        $service = new \stdClass();

        $this->innerServiceLocator->expects(self::once())
            ->method('get')
            ->with($serviceId)
            ->willReturn($service);

        // First call - should fetch from inner locator
        self::assertSame($service, $this->serviceLocatorDecorator->get($serviceId));

        // Second call - should return cached service without calling inner locator
        self::assertSame($service, $this->serviceLocatorDecorator->get($serviceId));

        // Third call - verify cache still works
        self::assertSame($service, $this->serviceLocatorDecorator->get($serviceId));
    }

    public function testGetWhenServiceDoesNotExist(): void
    {
        $serviceId = 'test.service';

        $this->innerServiceLocator->expects(self::once())
            ->method('get')
            ->with($serviceId)
            ->willReturn(null);

        // First call - should fetch from inner locator
        self::assertNull($this->serviceLocatorDecorator->get($serviceId));

        // Second call - should return cached service without calling inner locator
        self::assertNull($this->serviceLocatorDecorator->get($serviceId));

        // Third call - verify cache still works
        self::assertNull($this->serviceLocatorDecorator->get($serviceId));
    }

    /**
     * @dataProvider hasDataProvider
     */
    public function testHas(bool $result): void
    {
        $serviceId = 'test.service';

        $this->innerServiceLocator->expects(self::once())
            ->method('has')
            ->with($serviceId)
            ->willReturn($result);

        self::assertSame($result, $this->serviceLocatorDecorator->has($serviceId));
    }

    public static function hasDataProvider(): array
    {
        return [[false], [true]];
    }
}
