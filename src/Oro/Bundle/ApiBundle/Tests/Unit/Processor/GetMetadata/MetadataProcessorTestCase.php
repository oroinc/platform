<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtension;
use Oro\Bundle\ApiBundle\Config\SortersConfigExtension;
use Oro\Bundle\ApiBundle\Filter\FilterOperatorRegistry;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class MetadataProcessorTestCase extends \PHPUnit\Framework\TestCase
{
    protected const TEST_CLASS_NAME   = 'Test\Class';
    protected const TEST_VERSION      = '1.1';
    protected const TEST_REQUEST_TYPE = RequestType::REST;

    /** @var MetadataContext */
    protected $context;

    /** @var ConfigExtensionRegistry */
    protected $configExtensionRegistry;

    /** @var ConfigLoaderFactory */
    protected $configLoaderFactory;

    protected function setUp()
    {
        $this->context = new MetadataContext();
        $this->context->setClassName(self::TEST_CLASS_NAME);
        $this->context->setVersion(self::TEST_VERSION);
        $this->context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $this->configExtensionRegistry = new ConfigExtensionRegistry();
        $this->configExtensionRegistry->addExtension(new FiltersConfigExtension(new FilterOperatorRegistry([])));
        $this->configExtensionRegistry->addExtension(new SortersConfigExtension());

        $this->configLoaderFactory = new ConfigLoaderFactory($this->configExtensionRegistry);
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
