<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\ApiBundle\Config\ActionsConfigExtension;
use Oro\Bundle\ApiBundle\Config\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtension;
use Oro\Bundle\ApiBundle\Config\SortersConfigExtension;
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

    /** @var ConfigExtensionRegistry */
    protected $configExtensionRegistry;

    /** @var ConfigLoaderFactory */
    protected $configLoaderFactory;

    protected function setUp()
    {
        $this->context = new ConfigContext();
        $this->context->setClassName(self::TEST_CLASS_NAME);
        $this->context->setVersion(self::TEST_VERSION);
        $this->context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $this->configExtensionRegistry = new ConfigExtensionRegistry();
        $this->configExtensionRegistry->addExtension(new FiltersConfigExtension());
        $this->configExtensionRegistry->addExtension(new SortersConfigExtension());
        $this->configExtensionRegistry->addExtension(new ActionsConfigExtension());

        $this->configLoaderFactory = new ConfigLoaderFactory($this->configExtensionRegistry);
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
     * @param string|null $className
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ClassMetadata
     */
    protected function getClassMetadataMock($className = null)
    {
        if ($className) {
            $classMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
                ->setConstructorArgs([$className])
                ->getMock();
        } else {
            $classMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
                ->disableOriginalConstructor()
                ->getMock();
        }
        $classMetadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_NONE;

        return $classMetadata;
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
