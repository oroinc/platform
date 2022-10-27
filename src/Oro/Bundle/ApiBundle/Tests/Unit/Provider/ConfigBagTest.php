<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\ConfigBag;
use Oro\Bundle\ApiBundle\Provider\ConfigCache;
use Oro\Bundle\ApiBundle\Request\Version;

class ConfigBagTest extends \PHPUnit\Framework\TestCase
{
    public function getConfigBag(int $numberOfGetConfigCalls = 1): ConfigBag
    {
        $configFile = 'api.yml';
        $config = [];
        foreach (['metadata', 'entities'] as $section) {
            $config[$section] = [
                'Test\Class1' => [
                    'fields' => ['class1_v0' => []]
                ],
                'Test\Class2' => [
                    'fields' => ['class2_v2.0' => []]
                ]
            ];
        }

        $configCache = $this->createMock(ConfigCache::class);
        $configCache->expects(self::exactly($numberOfGetConfigCalls))
            ->method('getConfig')
            ->with($configFile)
            ->willReturn($config);

        return new ConfigBag($configCache, $configFile);
    }

    public function testGetClassNames()
    {
        $version = Version::LATEST;
        $expectedEntityClasses = ['Test\Class1', 'Test\Class2'];

        $configBag = $this->getConfigBag();

        self::assertEquals($expectedEntityClasses, $configBag->getClassNames($version));
        // test that data is cached in memory
        self::assertEquals($expectedEntityClasses, $configBag->getClassNames($version));
    }

    public function testNoConfig()
    {
        $className = 'Test\UnknownClass';
        $version = Version::LATEST;

        $configBag = $this->getConfigBag();

        self::assertNull($configBag->getConfig($className, $version));
        // test that data is cached in memory
        self::assertNull($configBag->getConfig($className, $version));
    }

    /**
     * @dataProvider getConfigProvider
     */
    public function testGetConfig(string $className, string $version, array $expectedConfig)
    {
        $configBag = $this->getConfigBag();

        self::assertEquals($expectedConfig, $configBag->getConfig($className, $version));
        // test that data is cached in memory
        self::assertEquals($expectedConfig, $configBag->getConfig($className, $version));
    }

    public function getConfigProvider(): array
    {
        return [
            ['Test\Class1', '1.0', ['fields' => ['class1_v0' => []]]],
            ['Test\Class2', Version::LATEST, ['fields' => ['class2_v2.0' => []]]]
        ];
    }

    public function testReset()
    {
        $className = 'Test\Class2';
        $version = Version::LATEST;
        $expectedConfig = ['fields' => ['class2_v2.0' => []]];

        $configBag = $this->getConfigBag(2);

        self::assertEquals($expectedConfig, $configBag->getConfig($className, $version));
        // test that data is cached in memory
        self::assertEquals($expectedConfig, $configBag->getConfig($className, $version));

        $configBag->reset();
        self::assertEquals($expectedConfig, $configBag->getConfig($className, $version));
    }
}
