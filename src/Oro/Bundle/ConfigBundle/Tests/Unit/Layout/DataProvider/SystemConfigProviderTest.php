<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Layout\DataProvider\SystemConfigProvider;

class SystemConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var SystemConfigProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->provider = new SystemConfigProvider($this->configManager);
    }

    /**
     * @dataProvider getValueDataProvider
     *
     * @param array $arguments
     */
    public function testGetValue(array $arguments)
    {
        $this->configManager
            ->expects(static::once())
            ->method('get')
            ->withConsecutive($arguments);

        call_user_func_array([$this->provider, 'getValue'], $arguments);
    }

    /**
     * @return array
     */
    public function getValueDataProvider()
    {
        return [
            'with config parameter only' => [['oro_config.test']],
            'with config, default parameters' => [['oro_config.test', true]],
            'with config, default, full parameters' => [['oro_config.test', true, true]],
            'with config, default, full, scope identifier parameters' => [['oro_config.test', true, true, 1234]],
        ];
    }
}
