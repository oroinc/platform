<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extension\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\Extension\FiltersConfigExtension;
use Oro\Bundle\ApiBundle\Config\Extension\SortersConfigExtension;
use Oro\Bundle\ApiBundle\Config\Loader\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Filter\FilterOperatorRegistry;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class MetadataProcessorTestCase extends \PHPUnit\Framework\TestCase
{
    protected const TEST_CLASS_NAME = 'Test\Class';
    protected const TEST_VERSION = '1.1';
    protected const TEST_REQUEST_TYPE = RequestType::REST;

    protected MetadataContext $context;
    protected ConfigExtensionRegistry $configExtensionRegistry;
    protected ConfigLoaderFactory $configLoaderFactory;

    protected function setUp(): void
    {
        $this->context = new MetadataContext();
        $this->context->setClassName(self::TEST_CLASS_NAME);
        $this->context->setAction('get_metadata');
        $this->context->setVersion(self::TEST_VERSION);
        $this->context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $this->configExtensionRegistry = new ConfigExtensionRegistry();
        $this->configExtensionRegistry->addExtension(new FiltersConfigExtension(new FilterOperatorRegistry([])));
        $this->configExtensionRegistry->addExtension(new SortersConfigExtension());

        $this->configLoaderFactory = new ConfigLoaderFactory($this->configExtensionRegistry);
    }

    protected function createConfigObject(array $config): EntityDefinitionConfig
    {
        return $this->configLoaderFactory->getLoader(ConfigUtil::DEFINITION)->load($config);
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
