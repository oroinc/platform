<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\ConfigBag;
use Oro\Bundle\ApiBundle\Provider\ConfigCache;
use Oro\Bundle\ApiBundle\Request\Version;

class ConfigBagTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigBag */
    private $configBag;

    protected function setUp()
    {
        $config = [];
        foreach (['metadata', 'entities', 'relations'] as $section) {
            $config[$section] = [
                'Test\Class1' => [
                    'fields' => ['class1_v0' => []]
                ],
                'Test\Class2' => [
                    'fields' => ['class2_v2.0' => []]
                ]
            ];
        }

        $configFile = 'api.yml';
        $configCache = $this->createMock(ConfigCache::class);
        $configCache->expects(self::once())
            ->method('getConfig')
            ->with($configFile)
            ->willReturn($config);
        $this->configBag = new ConfigBag($configCache, $configFile);
    }

    public function testGetClassNames()
    {
        $version = Version::LATEST;
        $expectedEntityClasses = ['Test\Class1', 'Test\Class2'];

        self::assertEquals($expectedEntityClasses, $this->configBag->getClassNames($version));
        // test that data is cached in memory
        self::assertEquals($expectedEntityClasses, $this->configBag->getClassNames($version));
    }

    public function testNoConfig()
    {
        $className = 'Test\UnknownClass';
        $version = Version::LATEST;

        self::assertNull($this->configBag->getConfig($className, $version));
        // test that data is cached in memory
        self::assertNull($this->configBag->getConfig($className, $version));
    }

    public function testNoRelationConfig()
    {
        $className = 'Test\UnknownClass';
        $version = Version::LATEST;

        self::assertNull($this->configBag->getRelationConfig($className, $version));
        // test that data is cached in memory
        self::assertNull($this->configBag->getRelationConfig($className, $version));
    }

    /**
     * @dataProvider getConfigProvider
     */
    public function testGetConfig($className, $version, $expectedConfig)
    {
        self::assertEquals(
            $expectedConfig,
            $this->configBag->getConfig($className, $version)
        );
        // test that data is cached in memory
        self::assertEquals(
            $expectedConfig,
            $this->configBag->getConfig($className, $version)
        );
    }

    /**
     * @dataProvider getConfigProvider
     */
    public function testGetRelationConfig($className, $version, $expectedConfig)
    {
        self::assertEquals(
            $expectedConfig,
            $this->configBag->getRelationConfig($className, $version)
        );
        // test that data is cached in memory
        self::assertEquals(
            $expectedConfig,
            $this->configBag->getRelationConfig($className, $version)
        );
    }

    public function getConfigProvider()
    {
        return [
            ['Test\Class1', '1.0', ['fields' => ['class1_v0' => []]]],
            ['Test\Class2', Version::LATEST, ['fields' => ['class2_v2.0' => []]]]
        ];
    }
}
