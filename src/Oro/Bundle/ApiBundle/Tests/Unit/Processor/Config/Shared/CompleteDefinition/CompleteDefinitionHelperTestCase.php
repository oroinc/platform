<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared\CompleteDefinition;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class CompleteDefinitionHelperTestCase extends \PHPUnit\Framework\TestCase
{
    protected const TEST_CLASS_NAME   = 'Test\Class';
    protected const TEST_VERSION      = '1.1';
    protected const TEST_REQUEST_TYPE = RequestType::REST;

    /** @var ConfigLoaderFactory */
    protected $configLoaderFactory;

    protected function setUp()
    {
        $this->configLoaderFactory = new ConfigLoaderFactory(new ConfigExtensionRegistry());
    }

    /**
     * @param array $config
     *
     * @return EntityDefinitionConfig
     */
    protected function createConfigObject(array $config)
    {
        return $this->configLoaderFactory->getLoader(ConfigUtil::DEFINITION)->load($config);
    }

    /**
     * @param array|null $definition
     *
     * @return Config
     */
    protected function createRelationConfigObject(array $definition = null)
    {
        $config = new Config();
        if (null !== $definition) {
            $config->setDefinition($this->createConfigObject($definition));
        }

        return $config;
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
        self::assertEquals(
            $expected,
            $this->convertConfigObjectToArray($actual)
        );
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

        return $classMetadata;
    }
}
