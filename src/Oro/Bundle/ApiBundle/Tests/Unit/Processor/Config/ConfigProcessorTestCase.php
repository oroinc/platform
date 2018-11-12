<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\ActionsConfigExtension;
use Oro\Bundle\ApiBundle\Config\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtension;
use Oro\Bundle\ApiBundle\Config\SortersConfigExtension;
use Oro\Bundle\ApiBundle\Config\SubresourcesConfigExtension;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterOperatorRegistry;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Request\ApiActions;
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

    protected function setUp()
    {
        $this->context = new ConfigContext();
        $this->context->setClassName(self::TEST_CLASS_NAME);
        $this->context->setVersion(self::TEST_VERSION);
        $this->context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $actionProcessorBag = $this->createMock(ActionProcessorBagInterface::class);
        $actionProcessorBag->expects(self::any())
            ->method('getActions')
            ->willReturn([
                ApiActions::GET,
                ApiActions::GET_LIST,
                ApiActions::UPDATE,
                ApiActions::CREATE,
                ApiActions::DELETE,
                ApiActions::DELETE_LIST,
                ApiActions::GET_SUBRESOURCE,
                ApiActions::GET_RELATIONSHIP,
                ApiActions::UPDATE_RELATIONSHIP,
                ApiActions::ADD_RELATIONSHIP,
                ApiActions::DELETE_RELATIONSHIP
            ]);
        $filterOperatorRegistry = new FilterOperatorRegistry([
            ComparisonFilter::EQ              => '=',
            ComparisonFilter::NEQ             => '!=',
            ComparisonFilter::GT              => '>',
            ComparisonFilter::LT              => '<',
            ComparisonFilter::GTE             => '>=',
            ComparisonFilter::LTE             => '<=',
            ComparisonFilter::EXISTS          => '*',
            ComparisonFilter::NEQ_OR_NULL     => '!*',
            ComparisonFilter::CONTAINS        => '~',
            ComparisonFilter::NOT_CONTAINS    => '!~',
            ComparisonFilter::STARTS_WITH     => '^',
            ComparisonFilter::NOT_STARTS_WITH => '!^',
            ComparisonFilter::ENDS_WITH       => '$',
            ComparisonFilter::NOT_ENDS_WITH   => '!$'
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
