<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Provider\Console;

use Oro\Bundle\PlatformBundle\Provider\Console\GlobalOptionsProviderInterface;
use Oro\Bundle\PlatformBundle\Provider\Console\GlobalOptionsProviderRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

class GlobalOptionsProviderRegistryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var GlobalOptionsProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $firstProvider;

    /**
     * @var GlobalOptionsProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $secondProvider;

    /**
     * @var GlobalOptionsProviderRegistry
     */
    private $registry;

    protected function setUp()
    {
        $this->firstProvider = $this->createMock(GlobalOptionsProviderInterface::class);
        $this->secondProvider = $this->createMock(GlobalOptionsProviderInterface::class);
        $this->registry = new GlobalOptionsProviderRegistry();
        $this->registry->registerProvider($this->firstProvider);
        $this->registry->registerProvider($this->secondProvider);
    }

    public function testAddGlobalOptions()
    {
        /** @var Command $command */
        $command = $this->createMock(Command::class);
        $this->firstProvider->expects($this->once())
            ->method('addGlobalOptions')
            ->with($command);
        $this->secondProvider->expects($this->once())
            ->method('addGlobalOptions')
            ->with($command);

        $this->registry->addGlobalOptions($command);
    }

    public function testResolveGlobalOptions()
    {
        /** @var InputInterface $input */
        $input = $this->createMock(InputInterface::class);
        $this->firstProvider->expects($this->once())
            ->method('resolveGlobalOptions')
            ->with($input);
        $this->secondProvider->expects($this->once())
            ->method('resolveGlobalOptions')
            ->with($input);

        $this->registry->resolveGlobalOptions($input);
    }
}
