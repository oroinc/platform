<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Form\Provider;

use Oro\Bundle\ThemeBundle\Exception\ConfigurationBuilderNotFoundException;
use Oro\Bundle\ThemeBundle\Form\Provider\ConfigurationBuildersProvider;
use Oro\Bundle\ThemeBundle\Tests\Unit\Stubs\ConfigurationChildBuilderStub;
use PHPUnit\Framework\TestCase;

final class ConfigurationBuildersProviderTest extends TestCase
{
    private ConfigurationBuildersProvider $configurationBuildersProvider;

    private ConfigurationChildBuilderStub $childBuilderStub;

    protected function setUp(): void
    {
        $this->childBuilderStub = new ConfigurationChildBuilderStub();

        $this->configurationBuildersProvider = new ConfigurationBuildersProvider([$this->childBuilderStub]);
    }

    public function testThatConfigurationTypesListReturned(): void
    {
        self::assertEquals(['type'], $this->configurationBuildersProvider->getConfigurationTypes());
    }

    public function testThatExceptionThrowWhenConfigurationTypeNotFound(): void
    {
        self::expectException(ConfigurationBuilderNotFoundException::class);

        $this->configurationBuildersProvider->getConfigurationBuilderByOption(['type' => 'no_exists']);
    }

    public function testThatConfigurationReturnedByOption(): void
    {
        self::assertEquals(
            $this->childBuilderStub,
            $this->configurationBuildersProvider->getConfigurationBuilderByOption(['type' => 'type'])
        );
    }
}
