<?php

namespace Oro\Bundle\EntityConfigBundle\Config\Validation;

use Oro\Bundle\EntityConfigBundle\Config\Definition\Builder\NormalizedBooleanNodeDefinition;
use Oro\Bundle\EntityConfigBundle\Exception\LogicException;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This manager collect all config validation classes
 */
class ConfigurationManager
{
    /** @var TreeBuilder[] */
    private array $configurations;

    /** @var string[] */
    private array $classes;

    /**
     * ConfigurationManager constructor.
     */
    public function __construct(iterable $configurations)
    {
        foreach ($configurations as $configuration) {
            $this->registerValidation($configuration);
        }
    }

    private function registerValidation(ConfigInterface $configuration): void
    {
        if ($configuration instanceof FieldConfigInterface) {
            $type = ConfigurationValidator::CONFIG_FIELD_TYPE;
        } elseif ($configuration instanceof EntityConfigInterface) {
            $type = ConfigurationValidator::CONFIG_ENTITY_TYPE;
        } else {
            throw new LogicException('Wrong validation type trying to register');
        }

        $sectionName = $configuration->getSectionName();

        if (empty($this->configurations[$type][$sectionName])) {
            $this->configurations[$type][$sectionName] = new TreeBuilder($sectionName);
        }

        $this->classes[$type][$sectionName] = $configuration::class;
        $node = $this->configurations[$type][$sectionName]->getRootNode()
            ->children()
            ->setNodeClass('normalized_boolean', NormalizedBooleanNodeDefinition::class);
        $configuration->configure($node);
    }

    public function getClass(int $type, string $name): ?string
    {
        return $this->classes[$type][$name] ?? null;
    }

    public function getConfiguration(int $type, string $name): ?ConfigurationInterface
    {
        if (isset($this->configurations[$type][$name])) {
            return $this->buildConfiguration($this->configurations[$type][$name]);
        }

        return null;
    }

    public function getConfigAttributeNamesByType(int $type): ?array
    {
        if (isset($this->configurations[$type])) {
            $names = array_keys((array)$this->configurations[$type]);
            sort($names);
            return $names;
        }
        return null;
    }

    private function buildConfiguration(TreeBuilder $configuration): ConfigurationInterface
    {
        return new Configuration($configuration);
    }
}
