<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\Extension\ActionsConfigExtension;
use Oro\Bundle\ApiBundle\Config\Extension\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\Extension\FiltersConfigExtension;
use Oro\Bundle\ApiBundle\Config\Extension\SortersConfigExtension;
use Oro\Bundle\ApiBundle\Config\Extension\SubresourcesConfigExtension;
use Oro\Bundle\ApiBundle\Config\Loader\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Filter\FilterOperator;
use Oro\Bundle\ApiBundle\Filter\FilterOperatorRegistry;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class ConfigProcessorTestCase extends \PHPUnit\Framework\TestCase
{
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

        $actionProcessorBag = $this->createMock(ActionProcessorBagInterface::class);
        $actionProcessorBag->expects(self::any())
            ->method('getActions')
            ->willReturn([
                ApiAction::GET,
                ApiAction::GET_LIST,
                ApiAction::UPDATE,
                ApiAction::CREATE,
                ApiAction::DELETE,
                ApiAction::DELETE_LIST,
                ApiAction::GET_SUBRESOURCE,
                ApiAction::GET_RELATIONSHIP,
                ApiAction::UPDATE_RELATIONSHIP,
                ApiAction::ADD_RELATIONSHIP,
                ApiAction::DELETE_RELATIONSHIP
            ]);
        $filterOperatorRegistry = new FilterOperatorRegistry([
            FilterOperator::EQ              => '=',
            FilterOperator::NEQ             => '!=',
            FilterOperator::GT              => '>',
            FilterOperator::LT              => '<',
            FilterOperator::GTE             => '>=',
            FilterOperator::LTE             => '<=',
            FilterOperator::EXISTS          => '*',
            FilterOperator::NEQ_OR_NULL     => '!*',
            FilterOperator::CONTAINS        => '~',
            FilterOperator::NOT_CONTAINS    => '!~',
            FilterOperator::STARTS_WITH     => '^',
            FilterOperator::NOT_STARTS_WITH => '!^',
            FilterOperator::ENDS_WITH       => '$',
            FilterOperator::NOT_ENDS_WITH   => '!$'
        ]);

        $this->configExtensionRegistry = new ConfigExtensionRegistry();
        $this->configExtensionRegistry->addExtension(new FiltersConfigExtension($filterOperatorRegistry));
        $this->configExtensionRegistry->addExtension(new SortersConfigExtension());
        $this->configExtensionRegistry->addExtension(new ActionsConfigExtension($actionProcessorBag));
        $this->configExtensionRegistry->addExtension(
            new SubresourcesConfigExtension($actionProcessorBag, $filterOperatorRegistry)
        );

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
