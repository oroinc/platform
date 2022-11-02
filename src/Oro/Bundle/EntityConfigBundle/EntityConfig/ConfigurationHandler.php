<?php

namespace Oro\Bundle\EntityConfigBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\Config\Definition\NormalizedBooleanNodeDefinition;
use Oro\Bundle\EntityConfigBundle\Exception\EntityConfigValidationException;
use Oro\Bundle\EntityConfigBundle\Exception\LogicException;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityConfigBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderBag;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

/**
 * Validates and processes entity config configuration
 */
class ConfigurationHandler
{
    public const CONFIG_ENTITY_TYPE = 1;
    public const CONFIG_FIELD_TYPE = 2;

    /** @var TreeBuilder[] */
    private array $configurations;

    private ConfigProviderBag $providerBag;

    public function __construct(iterable $configurations)
    {
        foreach ($configurations as $configuration) {
            $this->registerConfiguration($configuration);
        }
    }

    private function registerConfiguration(ConfigInterface $configuration): void
    {
        if ($configuration instanceof FieldConfigInterface) {
            $type = ConfigurationHandler::CONFIG_FIELD_TYPE;
        } elseif ($configuration instanceof EntityConfigInterface) {
            $type = ConfigurationHandler::CONFIG_ENTITY_TYPE;
        } else {
            throw new LogicException('Wrong validation type trying to register');
        }

        $sectionName = $configuration->getSectionName();

        if (empty($this->configurations[$type][$sectionName])) {
            $this->configurations[$type][$sectionName] = new TreeBuilder($sectionName);
        }

        $node = $this->configurations[$type][$sectionName]->getRootNode()
            ->children()
            ->setNodeClass('normalized_boolean', NormalizedBooleanNodeDefinition::class);
        $configuration->configure($node);
    }

    public function getConfiguration(int $type, string $scope): ?ConfigurationInterface
    {
        if (isset($this->configurations[$type][$scope])) {
            return new Configuration($this->configurations[$type][$scope]);
        }

        return null;
    }

    public function getAvailableScopes(int $type): ?array
    {
        if (isset($this->configurations[$type])) {
            $scopes = array_keys((array)$this->configurations[$type]);
            sort($scopes);
            return $scopes;
        }
        return null;
    }

    public function setProviderBag(ConfigProviderBag $providerBag): void
    {
        $this->providerBag = $providerBag;
    }

    public function validate(int $type, string $scope, array $values, string $entityOrTableName): void
    {
        // unset values to skip
        foreach ($values as $fieldName => $fieldValue) {
            if (preg_match("/[^.]+\./", $fieldName)) {
                unset($values[$fieldName]);
            }
        }

        $this->validateScope($scope, $type, $entityOrTableName);
        $this->process($type, $scope, $values, $entityOrTableName);
    }

    public function process(
        int $type,
        string $scope,
        array $values,
        string $entityOrTableName = null,
        string $fieldType = null
    ): array {
        $configuration = $this->getConfiguration($type, $scope);
        if (empty($values) && empty($configuration)) {
            return $values;
        }

        $this->validateScope($scope, $type, $entityOrTableName);

        try {
            $processor = new Processor();

            return $this->filterConfig(
                $processor->processConfiguration(
                    $configuration,
                    [$scope => $values]
                ),
                $values,
                $scope,
                $fieldType
            );
        } catch (InvalidConfigurationException $exception) {
            if ($type === self::CONFIG_FIELD_TYPE) {
                $message = 'Invalid entity field config';
            } else {
                $message = 'Invalid entity config';
            }
            if ($entityOrTableName) {
                $message .= sprintf(' for "%s"', $entityOrTableName);
            }
            throw new EntityConfigValidationException(
                sprintf(
                    '%s: %s',
                    $message,
                    $exception->getMessage()
                ),
                $exception->getCode()
            );
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function filterConfig(
        array $configValues,
        array $incomingConfigValues,
        string $scope,
        string $fieldType = null
    ): array {
        foreach ($configValues as $configName => $configValue) {
            if (is_array($configValue) && !count($configValue)) {
                unset($configValues[$configName]);
            }
        }

        if ($fieldType === null) {
            return $configValues;
        }

        $propertyConfig = $this->getPropertyConfig($scope);
        $type = PropertyConfigContainer::TYPE_FIELD;
        $config = $propertyConfig->getConfig();

        if (empty($config[$type]['items'])) {
            return $configValues;
        }

        foreach ($config[$type]['items'] as $code => $item) {
            if (!isset($incomingConfigValues[$code])
                && isset($configValues[$code])
                && isset($item['options']['allowed_type'])
                && !in_array($fieldType, $item['options']['allowed_type'], true)
            ) {
                unset($configValues[$code]);
            }
        }

        return $configValues;
    }

    protected function getPropertyConfig(string $scope): PropertyConfigContainer
    {
        return $this->providerBag->getProvider($scope)->getPropertyConfig();
    }


    /**
     * @param FieldMetadata|EntityMetadata|null $metadata
     * @param array                             $providers
     * @param string                            $entityOrTableName
     */
    public function validateScopes($metadata, array $providers, string $entityOrTableName): void
    {
        if (null === $metadata || empty($metadata->defaultValues)) {
            return;
        }
        $invalidScopes = array_diff_key($metadata->defaultValues, $providers);
        if (empty($invalidScopes)) {
            return;
        }

        $type = $metadata instanceof FieldMetadata ? self::CONFIG_FIELD_TYPE : self::CONFIG_ENTITY_TYPE;

        $this->validateScope(array_key_first($invalidScopes), $type, $entityOrTableName);
    }

    private function validateScope(string $scope, int $type, string $entityOrTableName = null): void
    {
        $availableScopes = $this->getAvailableScopes($type);
        if (!in_array($scope, $availableScopes)) {
            if ($type === self::CONFIG_FIELD_TYPE) {
                $message = 'Invalid entity field config';
            } else {
                $message = 'Invalid entity config';
            }
            if ($entityOrTableName) {
                $message .= sprintf(' for "%s"', $entityOrTableName);
            }
            throw new EntityConfigValidationException(
                sprintf(
                    '%s: Unrecognized scope "%s". Available scopes are "%s".',
                    $message,
                    $scope,
                    implode('", "', $availableScopes)
                )
            );
        }
    }
}
