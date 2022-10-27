<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Provider\Console;

use Oro\Bundle\PlatformBundle\Provider\Console\GlobalOptionsProviderInterface;
use Oro\Bundle\PlatformBundle\Provider\Console\GlobalOptionsProviderRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

class GlobalOptionsProviderRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var GlobalOptionsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $firstProvider;

    /** @var GlobalOptionsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $secondProvider;

    /** @var GlobalOptionsProviderRegistry */
    private $registry;

    protected function setUp(): void
    {
        $this->firstProvider = $this->createMock(GlobalOptionsProviderInterface::class);
        $this->secondProvider = $this->createMock(GlobalOptionsProviderInterface::class);

        $this->registry = new GlobalOptionsProviderRegistry(
            [$this->firstProvider, $this->secondProvider]
        );
    }

    public function testAddGlobalOptions()
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

    public function testResolveGlobalOptions()
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
