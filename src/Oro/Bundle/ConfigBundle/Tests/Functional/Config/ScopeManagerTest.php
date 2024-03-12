<?php

namespace Oro\Bundle\ConfigBundle\Tests\Functional\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\DataFixtures\LoadConfigValue;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ScopeManagerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private ?ConfigManager $configManager = null;

    protected function setUp(): void
    {
        $this->initClient();
        $this->configManager = self::getConfigManager();

        $this->loadFixtures([LoadConfigValue::class]);
    }

    /**
     * @dataProvider booleanValuesDataProvider
     */
    public function testSetBooleanValues(mixed $value, mixed $expected): void
    {
        $key = 'oro_test_framework.boolean_type';
        $this->configManager->set($key, $value);
        $this->configManager->flush();

        self::assertSame($expected, $this->configManager->get($key));
    }

    public function booleanValuesDataProvider(): array
    {
        return [
            ['value' => 'yes', 'expected' => true],
            ['value' => 'no', 'expected' => false],
            ['value' => 'true', 'expected' => true],
            ['value' => 'false', 'expected' => false],
            ['value' => '1', 'expected' => true],
            ['value' => '0', 'expected' => false],
            ['value' => true, 'expected' => true],
            ['value' => false, 'expected' => false],
            ['value' => 1, 'expected' => true],
            ['value' => 0, 'expected' => false]
        ];
    }

    /**
     * @dataProvider integerValuesDataProvider
     */
    public function testSetIntegerValues(mixed $value, mixed $expected): void
    {
        $key = 'oro_test_framework.integer_type';
        $this->configManager->set($key, $value);
        $this->configManager->flush();

        self::assertSame($expected, $this->configManager->get($key));
    }

    public function integerValuesDataProvider(): array
    {
        return [
            ['value' => '123', 'expected' => 123],
            ['value' => '0', 'expected' => 0],
            ['value' => '0123', 'expected' => 123],
            ['value' => '1230', 'expected' => 1230],
            ['value' => 123, 'expected' => 123],
            ['value' => 222.1, 'expected' => 222],
        ];
    }

    /**
     * @dataProvider floatValuesDataProvider
     */
    public function testSetFloatValues(mixed $value, mixed $expected): void
    {
        $key = 'oro_test_framework.float_type';
        $this->configManager->set($key, $value);
        $this->configManager->flush();

        self::assertSame($expected, $this->configManager->get($key));
    }

    public function floatValuesDataProvider(): array
    {
        return [
            ['value' => '123.1', 'expected' => 123.1],
            ['value' => '01', 'expected' => 1.0],
            ['value' => '0123.1', 'expected' => 123.1],
            ['value' => '1230', 'expected' => 1230.0],
            ['value' => '0', 'expected' => 0.0],
            ['value' => 123.1, 'expected' => 123.1]
        ];
    }
}
