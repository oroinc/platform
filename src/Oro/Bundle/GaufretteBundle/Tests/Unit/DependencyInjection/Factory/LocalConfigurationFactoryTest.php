<?php

namespace Oro\Bundle\GaufretteBundle\Tests\Unit\DependencyInjection\Factory;

use Oro\Bundle\GaufretteBundle\DependencyInjection\Factory\LocalConfigurationFactory;

class LocalConfigurationFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var LocalConfigurationFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->factory = new LocalConfigurationFactory();
    }

    public function testGetAdapterConfiguration()
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

    public function testGetKey()
    {
        self::assertEquals('local', $this->factory->getKey());
    }

    public function testGetHint()
    {
        self::assertEquals(
            'The configuration string is "local:{directory}",'
            . ' for example "local:%kernel.project_dir%/public/media".',
            $this->factory->getHint()
        );
    }
}
