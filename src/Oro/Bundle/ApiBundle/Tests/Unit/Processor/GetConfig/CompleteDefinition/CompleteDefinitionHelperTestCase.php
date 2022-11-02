<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig\CompleteDefinition;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extension\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\Loader\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class CompleteDefinitionHelperTestCase extends \PHPUnit\Framework\TestCase
{
    protected const TEST_CLASS_NAME = 'Test\Class';
    protected const TEST_VERSION = '1.1';
    protected const TEST_REQUEST_TYPE = RequestType::REST;

    protected ConfigLoaderFactory $configLoaderFactory;

    protected function setUp(): void
    {
        $this->configLoaderFactory = new ConfigLoaderFactory(new ConfigExtensionRegistry());
    }

    protected function createConfigObject(array $config): EntityDefinitionConfig
    {
        return $this->configLoaderFactory->getLoader(ConfigUtil::DEFINITION)->load($config);
    }

    protected function createRelationConfigObject(array $definition = null): Config
    {
        $config = new Config();
        if (null !== $definition) {
            $config->setDefinition($this->createConfigObject($definition));
        }

        return $config;
    }

    protected function convertConfigObjectToArray(object|array $config): array
    {
        return is_object($config)
            ? $config->toArray()
            : $config;
    }

    protected function assertConfig(array $expected, object|array $actual): void
    {
        self::assertEquals(
            $expected,
            $this->convertConfigObjectToArray($actual)
        );
    }

    protected function getClassMetadataMock(
        string $className = null
    ): ClassMetadata|\PHPUnit\Framework\MockObject\MockObject {
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
}
