<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Title\TitleReader;

use Oro\Bundle\NavigationBundle\Provider\ConfigurationProvider;
use Oro\Bundle\NavigationBundle\Title\TitleReader\ConfigReader;

class ConfigReaderTest extends \PHPUnit\Framework\TestCase
{
    const TEST_ROUTE = 'test_route';

    /** @var ConfigurationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configurationProvider;

    /** @var ConfigReader */
    private $reader;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->configurationProvider = $this->getMockBuilder(ConfigurationProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configurationProvider
            ->expects($this->once())
            ->method('getConfiguration')
            ->with(ConfigurationProvider::TITLES_KEY)
            ->willReturn([self::TEST_ROUTE => 'Test title template']);

        $this->reader = new ConfigReader($this->configurationProvider);
    }

    public function testGetTitle()
    {
        $title = $this->reader->getTitle(self::TEST_ROUTE);
        $this->assertEquals('Test title template', $title);
    }

    public function testGetTitleEmpty()
    {
        $title = $this->reader->getTitle('custom_route');
        $this->assertNull($title);
    }
}
