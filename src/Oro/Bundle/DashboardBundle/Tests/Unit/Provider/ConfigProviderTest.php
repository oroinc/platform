<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Provider;

use Oro\Bundle\DashboardBundle\Provider\ConfigProvider;

class ConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    public function testHasNoKeyException()
    {
        $this->configProvider = new ConfigProvider(array());

        $this->configProvider->hasConfig('not found config');
    }
}
