<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\DashboardType;

use Oro\Bundle\DashboardBundle\DashboardType\WidgetsDashboardTypeConfigProvider;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Model\ConfigProvider;

class WidgetsDashboardTypeConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    private $provider;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->provider = new WidgetsDashboardTypeConfigProvider($this->configProvider);
    }

    /**
     * @dataProvider isSupportedDataProvider
     */
    public function testIsSupported(?string $type, bool $isSupported): void
    {
        self::assertEquals($isSupported, $this->provider->isSupported($type));
    }

    public function isSupportedDataProvider(): array
    {
        return [
            [null, true],
            ['widgets', true],
            ['test', false],
        ];
    }

    public function testGetConfigWithoutName(): void
    {
        $dashboard = new Dashboard();

        $this->configProvider->expects(self::never())
            ->method('getDashboardConfig');

        self::assertEquals([], $this->provider->getConfig($dashboard));
    }

    public function testGetConfig(): void
    {
        $expectedConfig = ['label' => 'test'];

        $dashboard = new Dashboard();
        $dashboard->setName('test');

        $this->configProvider->expects(self::once())
            ->method('getDashboardConfig')
            ->with('test')
            ->willReturn($expectedConfig);

        self::assertEquals($expectedConfig, $this->provider->getConfig($dashboard));
    }
}
