<?php

namespace Oro\Bundle\ApiBundle\Config\Extension;

use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Provider\ResourceCheckerConfigProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationExtensionInterface;
use Oro\Bundle\FeatureToggleBundle\Configuration\ProcessConfigurationExtensionInterface;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Adds "api_resources" section to "Resources/config/oro/features.yml" configuration file.
 */
class FeatureConfigurationExtension implements ConfigurationExtensionInterface, ProcessConfigurationExtensionInterface
{
    private ActionProcessorBagInterface $actionProcessorBag;
    private ResourceCheckerConfigProvider $configProvider;
    private string $resourceType;
    private string $resourceDescription;

    public function __construct(
        ActionProcessorBagInterface $actionProcessorBag,
        ResourceCheckerConfigProvider $configProvider,
        string $resourceType,
        string $resourceDescription
    ) {
        $this->actionProcessorBag = $actionProcessorBag;
        $this->configProvider = $configProvider;
        $this->resourceType = $resourceType;
        $this->resourceDescription = $resourceDescription;
    }

    /**
     * {@inheritDoc}
     */
    public function extendConfigurationTree(NodeBuilder $node): void
    {
        $node
            ->arrayNode($this->resourceType)
                ->info($this->resourceDescription)
                ->example([
                    'Acme\AppBundle\Entity\Customer',
                    ['Acme\AppBundle\Entity\User', ['create', 'update', 'delete', 'delete_list']]
                ])
                ->prototype('variable')
                    ->validate()
                        ->always(function ($value) {
                            return $this->validateApiResource($value);
                        })
                    ->end()
                ->end()
            ->end();
    }

    /**
     * {@inheritDoc}
     */
    public function processConfiguration(array $configuration): array
    {
        $this->configProvider->startBuild();
        foreach ($configuration as $feature => $featureConfig) {
            $configuration[$feature] = $this->loadFeatureConfiguration($feature, $featureConfig);
        }
        $this->configProvider->flush();

        return $configuration;
    }

    /**
     * {@inheritDoc}
     */
    public function completeConfiguration(array $configuration): array
    {
        foreach ($configuration as $feature => $featureConfig) {
            $apiResources = $this->configProvider->getApiResources($feature);
            if ($apiResources) {
                $configuration[$feature][$this->resourceType] = array_merge(
                    $featureConfig[$this->resourceType] ?? [],
                    $apiResources
                );
            }
        }

        return $configuration;
    }

    /**
     * {@inheritDoc}
     */
    public function clearConfigurationCache(): void
    {
        $this->configProvider->clear();
    }

    private function loadFeatureConfiguration(string $feature, array $featureConfig): array
    {
        if (empty($featureConfig[$this->resourceType])) {
            return $featureConfig;
        }

        $actionDependedApiResourceKeys = [];
        foreach ($featureConfig[$this->resourceType] as $key => $apiResource) {
            if (\is_array($apiResource)) {
                $actionDependedApiResourceKeys[] = $key;
            }
        }
        if (!$actionDependedApiResourceKeys) {
            return $featureConfig;
        }

        foreach ($actionDependedApiResourceKeys as $key) {
            [$entityClass, $actions] = $featureConfig[$this->resourceType][$key];
            unset($featureConfig[$this->resourceType][$key]);
            if (\in_array(ApiAction::UPDATE, $actions, true)) {
                $actions[] = ApiAction::UPDATE_RELATIONSHIP;
                $actions[] = ApiAction::ADD_RELATIONSHIP;
                $actions[] = ApiAction::DELETE_RELATIONSHIP;
            }
            $this->configProvider->addApiResource($feature, $entityClass, $actions);
        }
        $featureConfig[$this->resourceType] = array_values($featureConfig[$this->resourceType]);

        return $featureConfig;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function validateApiResource(mixed $value): mixed
    {
        if (\is_array($value)) {
            if (ArrayUtil::isAssoc($value) || \count($value) !== 2) {
                throw new \InvalidArgumentException(
                    'The array value must contains 2 elements, an entity class and an array of API actions.'
                );
            }
            if (!\is_string($value[0])) {
                throw new \InvalidArgumentException(
                    'The first element of the array must be a string that is an entity class.'
                );
            }
            if (!\is_array($value[1]) || !$value[1]) {
                throw new \InvalidArgumentException('The second element of the array must not be an empty array.');
            }
            $actions = $this->actionProcessorBag->getActions();
            foreach ($value[1] as $action) {
                if (!\in_array($action, $actions, true)) {
                    throw new \InvalidArgumentException(sprintf(
                        'The "%s" is unknown API action. Known actions: "%s".',
                        $action,
                        implode(', ', $actions)
                    ));
                }
            }
        } elseif (!\is_string($value)) {
            throw new \InvalidArgumentException('The value must be a string or an array.');
        }

        return $value;
    }
}
