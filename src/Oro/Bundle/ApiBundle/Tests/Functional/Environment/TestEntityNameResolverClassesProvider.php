<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\Version;
use Oro\Bundle\EntityBundle\Tests\Functional\Environment\TestEntityNameResolverClassesProviderInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendClassLoadingUtils;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

class TestEntityNameResolverClassesProvider implements TestEntityNameResolverClassesProviderInterface
{
    private const REASON = 'api';

    private TestEntityNameResolverClassesProviderInterface $innerProvider;
    private RequestType $requestType;
    private ResourcesProvider $resourcesProvider;
    private ConfigProvider $configProvider;
    private ManagerRegistry $doctrine;

    public function __construct(
        TestEntityNameResolverClassesProviderInterface $innerProvider,
        array $requestType,
        ResourcesProvider $resourcesProvider,
        ConfigProvider $configProvider,
        ManagerRegistry $doctrine
    ) {
        $this->innerProvider = $innerProvider;
        $this->requestType = new RequestType($requestType);
        $this->resourcesProvider = $resourcesProvider;
        $this->configProvider = $configProvider;
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClasses(): array
    {
        $entityClasses = $this->innerProvider->getEntityClasses();
        $accessibleResources = $this->resourcesProvider->getAccessibleResources(Version::LATEST, $this->requestType);
        foreach ($accessibleResources as $entityClass) {
            if (str_starts_with($entityClass, ExtendClassLoadingUtils::getEntityNamespace())) {
                continue;
            }
            if (is_a($entityClass, TestFrameworkEntityInterface::class, true)) {
                continue;
            }
            if (null === $this->doctrine->getManagerForClass($entityClass)) {
                continue;
            }
            $config = $this->getApiConfig($entityClass);
            if (!$config->isMetaPropertyEnabled('title')) {
                continue;
            }
            if (!isset($entityClasses[$entityClass])) {
                $entityClasses[$entityClass] = [self::REASON];
            } elseif (!\in_array(self::REASON, $entityClasses[$entityClass], true)) {
                $entityClasses[$entityClass][] = self::REASON;
            }
        }

        return $entityClasses;
    }

    private function getApiConfig(string $entityClass): EntityDefinitionConfig
    {
        return $this->configProvider
            ->getConfig(
                $entityClass,
                Version::LATEST,
                $this->requestType,
                [new EntityDefinitionConfigExtra(ApiAction::GET_LIST)]
            )
            ->getDefinition();
    }
}
