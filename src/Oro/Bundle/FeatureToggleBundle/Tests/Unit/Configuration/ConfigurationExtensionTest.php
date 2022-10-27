<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Configuration;

use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationExtension;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationExtensionInterface;
use Oro\Bundle\FeatureToggleBundle\Tests\Unit\Fixtures\ConfigurationExtensionStub;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

class ConfigurationExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigurationExtensionInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $extension1;

    /** @var ConfigurationExtensionStub|\PHPUnit\Framework\MockObject\MockObject */
    private $extension2;

    /** @var ConfigurationExtension */
    private $configurationExtension;

    protected function setUp(): void
    {
        $this->extension1 = $this->createMock(ConfigurationExtensionInterface::class);
        $this->extension2 = $this->createMock(ConfigurationExtensionStub::class);

        $this->configurationExtension = new ConfigurationExtension([$this->extension1, $this->extension2]);
    }

    public function testExtendConfigurationTree(): void
    {
        $node = $this->createMock(NodeBuilder::class);

        $this->extension1->expects(self::once())
            ->method('extendConfigurationTree')
            ->with(self::identicalTo($node));
        $this->extension2->expects(self::once())
            ->method('extendConfigurationTree')
            ->with(self::identicalTo($node));

        $this->configurationExtension->extendConfigurationTree($node);
    }

    public function testProcessConfiguration(): void
    {
        $configuration = ['key' => 'value'];

        $this->extension1->expects(self::never())
            ->method(self::anything());
        $this->extension2->expects(self::once())
            ->method('processConfiguration')
            ->with($configuration)
            ->willReturnCallback(function (array $config) {
                $config['key1'] = 'value1';

                return $config;
            });

        self::assertEquals(
            ['key' => 'value', 'key1' => 'value1'],
            $this->configurationExtension->processConfiguration($configuration)
        );
    }

    public function testCompleteConfiguration(): void
    {
        $configuration = ['key' => 'value'];

        $this->extension1->expects(self::never())
            ->method(self::anything());
        $this->extension2->expects(self::once())
            ->method('completeConfiguration')
            ->with($configuration)
            ->willReturnCallback(function (array $config) {
                $config['key1'] = 'value1';

                return $config;
            });

        self::assertEquals(
            ['key' => 'value', 'key1' => 'value1'],
            $this->configurationExtension->completeConfiguration($configuration)
        );
    }

    public function testClearConfigurationCache(): void
    {
        $this->extension1->expects(self::never())
            ->method(self::anything());
        $this->extension2->expects(self::once())
            ->method('clearConfigurationCache');

        $this->configurationExtension->clearConfigurationCache();
    }
}
