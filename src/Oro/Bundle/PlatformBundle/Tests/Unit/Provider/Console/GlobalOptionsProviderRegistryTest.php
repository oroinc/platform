<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Provider\Console;

use Oro\Bundle\PlatformBundle\Provider\Console\GlobalOptionsProviderInterface;
use Oro\Bundle\PlatformBundle\Provider\Console\GlobalOptionsProviderRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

class GlobalOptionsProviderRegistryTest extends TestCase
{
    private GlobalOptionsProviderInterface&MockObject $firstProvider;
    private GlobalOptionsProviderInterface&MockObject $secondProvider;
    private GlobalOptionsProviderRegistry $registry;

    #[\Override]
    protected function setUp(): void
    {
        $this->firstProvider = $this->createMock(GlobalOptionsProviderInterface::class);
        $this->secondProvider = $this->createMock(GlobalOptionsProviderInterface::class);

        $this->registry = new GlobalOptionsProviderRegistry(
            [$this->firstProvider, $this->secondProvider]
        );
    }

    public function testAddGlobalOptions(): void
    {
        $command = $this->createMock(Command::class);

        $this->firstProvider->expects(self::once())
            ->method('addGlobalOptions')
            ->with($command);
        $this->secondProvider->expects(self::once())
            ->method('addGlobalOptions')
            ->with($command);

        $this->registry->addGlobalOptions($command);
    }

    public function testResolveGlobalOptions(): void
    {
        $input = $this->createMock(InputInterface::class);

        $this->firstProvider->expects(self::once())
            ->method('resolveGlobalOptions')
            ->with($input);
        $this->secondProvider->expects(self::once())
            ->method('resolveGlobalOptions')
            ->with($input);

        $this->registry->resolveGlobalOptions($input);
    }
}
