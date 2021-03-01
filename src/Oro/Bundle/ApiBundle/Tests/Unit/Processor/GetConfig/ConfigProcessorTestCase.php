<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\Extension\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\Loader\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Tests\Unit\Config\ConfigExtensionRegistryTrait;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class ConfigProcessorTestCase extends \PHPUnit\Framework\TestCase
{
    use ConfigExtensionRegistryTrait;

    protected const TEST_CLASS_NAME   = 'Test\Class';
    protected const TEST_VERSION      = '1.1';
    protected const TEST_REQUEST_TYPE = RequestType::REST;

    /** @var ConfigContext */
    protected $context;

    /** @var ConfigExtensionRegistry */
    protected $configExtensionRegistry;

    /** @var ConfigLoaderFactory */
    protected $configLoaderFactory;

    protected function setUp(): void
    {
        $this->context = new ConfigContext();
        $this->context->setClassName(self::TEST_CLASS_NAME);
        $this->context->setVersion(self::TEST_VERSION);
        $this->context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $this->configExtensionRegistry = $this->createConfigExtensionRegistry();
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
     * @return \PHPUnit\Framework\MockObject\MockObject|ClassMetadata
     */
    protected function getClassMetadataMock($className = null)
    {
        if ($className) {
            $classMetadata = $this->getMockBuilder(ClassMetadata::class)
                ->setConstructorArgs([$className])
                ->getMock();
        } else {
            $classMetadata = $this->createMock(ClassMetadata::class);
        }
        $classMetadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_NONE;
        $classMetadata->expects(self::any())
            ->method('isInheritanceTypeNone')
            ->willReturnCallback(function () use ($classMetadata) {
                return ClassMetadata::INHERITANCE_TYPE_NONE === $classMetadata->inheritanceType;
            });

        return $classMetadata;
    }

    /**
     * @param array        $expected
     * @param object|array $actual
     */
    protected function assertConfig(array $expected, $actual)
    {
        self::assertEquals(
            $expected,
            $this->convertConfigObjectToArray($actual)
        );
    }
}
