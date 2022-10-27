<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Suite;

use Behat\Testwork\Suite\Exception\ParameterNotFoundException;
use Oro\Bundle\TestFrameworkBundle\Behat\Suite\SymfonyBundleSuite;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class SymfonyBundleSuiteTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    private array $settings;

    private SymfonyBundleSuite $suite;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->settings = [
            'bundle' => 'OroFooBundle',
            'contexts' => ['context1', 'context2'],
            'paths' => ['path1', 'path2'],
        ];

        $this->suite = new SymfonyBundleSuite('name', $this->createMock(BundleInterface::class), $this->settings);
    }

    public function testAccessors(): void
    {
        self::assertPropertyAccessors(
            $this->suite,
            [
                ['name', 'name', false],
                ['bundle', $this->createMock(BundleInterface::class), false],
                ['settings', $this->settings, false],
            ]
        );
    }

    /**
     * @dataProvider hasSettingDataProvider
     */
    public function testHasSetting(string $key, bool $expectedValue): void
    {
        self::assertEquals($expectedValue, $this->suite->hasSetting($key));
    }

    public function hasSettingDataProvider(): array
    {
        return [
            'set' => [
                'key' => 'bundle',
                'expectedValue' => true,
            ],
            'not set' => [
                'key' => 'test',
                'expectedValue' => false,
            ],
        ];
    }

    public function testSettingKeyNotExists(): void
    {
        $this->expectException(ParameterNotFoundException::class);
        $this->expectExceptionMessage('`name` suite does not have a `test` setting.');

        $this->suite->getSetting('test');
    }
}
