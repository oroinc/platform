<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Title\TitleReader;

use Oro\Bundle\NavigationBundle\Provider\ConfigurationProvider;
use Oro\Bundle\NavigationBundle\Title\TitleReader\ConfigReader;

class ConfigReaderTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ROUTE = 'test_route';

    /** @var ConfigurationProvider|\PHPUnit_Framework_MockObject_MockObject */
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

    public function testGetDataSuccess()
    {
        try {
            $data = $this->reader->getData([self::TEST_ROUTE => 'Test route data']);

            $this->assertInternalType('array', $data);
            $this->assertCount(1, $data);
        } catch (\Exception $e) {
            $this->assertInstanceOf('Symfony\Component\Config\Definition\Exception\InvalidConfigurationException', $e);
        }
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testGetDataFailed()
    {
        $this->reader->getData([]);
    }
}
