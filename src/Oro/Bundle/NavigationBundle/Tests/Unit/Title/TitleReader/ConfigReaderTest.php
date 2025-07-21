<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Title\TitleReader;

use Oro\Bundle\NavigationBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\NavigationBundle\Title\TitleReader\ConfigReader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigReaderTest extends TestCase
{
    private const TEST_ROUTE = 'test_route';

    private ConfigurationProvider&MockObject $configurationProvider;
    private ConfigReader $reader;

    #[\Override]
    protected function setUp(): void
    {
        $this->configurationProvider = $this->createMock(ConfigurationProvider::class);

        $this->reader = new ConfigReader($this->configurationProvider);
    }

    public function testGetTitle(): void
    {
        $title = 'Test title template';

        $this->configurationProvider->expects(self::once())
            ->method('getTitle')
            ->with(self::TEST_ROUTE)
            ->willReturn($title);

        $title = $this->reader->getTitle(self::TEST_ROUTE);
        $this->assertEquals('Test title template', $title);
    }

    public function testGetTitleEmpty(): void
    {
        $this->configurationProvider->expects(self::once())
            ->method('getTitle')
            ->with(self::TEST_ROUTE)
            ->willReturn(null);

        $title = $this->reader->getTitle(self::TEST_ROUTE);
        $this->assertNull($title);
    }
}
