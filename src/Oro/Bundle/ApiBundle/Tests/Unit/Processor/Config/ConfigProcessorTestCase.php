<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config;

use Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class ConfigProcessorTestCase extends \PHPUnit_Framework_TestCase
{
    const TEST_CLASS_NAME   = 'Test\Class';
    const TEST_VERSION      = '1.1';
    const TEST_REQUEST_TYPE = RequestType::REST;

    /** @var ConfigContext */
    protected $context;

    /** @var ConfigLoaderFactory */
    protected $configLoaderFactory;

    protected function setUp()
    {
        $this->context = new ConfigContext();
        $this->context->setClassName(self::TEST_CLASS_NAME);
        $this->context->setVersion(self::TEST_VERSION);
        $this->context->setRequestType(self::TEST_REQUEST_TYPE);

        $this->configLoaderFactory = new ConfigLoaderFactory();
    }

    /**
     * @param array  $config
     * @param string $configType
     *
     * @return object
     */
    protected function createConfigObject(array $config, $configType = ConfigUtil::DEFINITION)
    {
        return $this->configLoaderFactory->getLoader($configType)->load($config);
    }

    /**
     * @param object|array $config
     *
     * @return array
     */
    protected function convertConfigObjectToArray($config)
    {
        return is_object($config)
            ? $config->toArray()
            : $config;
    }

    /**
     * @param array        $expected
     * @param object|array $actual
     */
    protected function assertConfig(array $expected, $actual)
    {
        $this->assertEquals(
            $expected,
            $this->convertConfigObjectToArray($actual)
        );
    }
}
