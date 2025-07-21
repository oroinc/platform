<?php

namespace Oro\Bundle\GaufretteBundle\Tests\Unit\DependencyInjection\Factory;

use Oro\Bundle\GaufretteBundle\DependencyInjection\Factory\LocalConfigurationFactory;
use PHPUnit\Framework\TestCase;

class LocalConfigurationFactoryTest extends TestCase
{
    private LocalConfigurationFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->factory = new LocalConfigurationFactory();
    }

    public function testGetAdapterConfiguration(): void
    {
        $configString = '/test';
        self::assertEquals(
            [
                'local' => [
                    'directory' => $configString
                ]
            ],
            $this->factory->getAdapterConfiguration($configString)
        );
    }

    public function testGetKey(): void
    {
        self::assertEquals('local', $this->factory->getKey());
    }

    public function testGetHint(): void
    {
        self::assertEquals(
            'The configuration string is "local:{directory}",'
            . ' for example "local:%kernel.project_dir%/public/media".',
            $this->factory->getHint()
        );
    }
}
