<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Title\TitleReader;

use Oro\Bundle\NavigationBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\NavigationBundle\Title\TitleReader\ConfigReader;

class ConfigReaderTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_ROUTE = 'test_route';

    /** @var ConfigurationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configurationProvider;

    /** @var ConfigReader */
    private $reader;

    protected function setUp(): void
    {
        $this->configurationProvider = $this->createMock(ConfigurationProvider::class);

        $this->reader = new ConfigReader($this->configurationProvider);
    }

    public function testGetTitle()
    {
        $title = 'Test title template';

        $this->configurationProvider->expects(self::once())
            ->method('getTitle')
            ->with(self::TEST_ROUTE)
            ->willReturn($title);

        $title = $this->reader->getTitle(self::TEST_ROUTE);
        $this->assertEquals('Test title template', $title);
    }

    public function testGetTitleEmpty()
    {
        $this->configurationProvider->expects(self::once())
            ->method('getTitle')
            ->with(self::TEST_ROUTE)
            ->willReturn(null);

        $title = $this->reader->getTitle(self::TEST_ROUTE);
        $this->assertNull($title);
    }
}
