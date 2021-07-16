<?php

namespace Oro\Bundle\EntityBundle\Twig\Sandbox;

use Psr\Container\ContainerInterface;

/**
 * Provides system and entity relates variables for sandboxed TWIG templates.
 */
class VariablesProvider
{
    /** @var ContainerInterface */
    private $providers;

    /** @var string[] */
    private $systemProviders;

    /** @var string[] */
    private $entityProviders;

    /**
     * @param ContainerInterface $providers
     * @param string[]           $systemProviders
     * @param string[]           $entityProviders
     */
    public function __construct(ContainerInterface $providers, array $systemProviders, array $entityProviders)
    {
        $this->providers = $providers;
        $this->systemProviders = $systemProviders;
        $this->entityProviders = $entityProviders;
    }

    /**
     * Gets system variables available in sandboxed TWIG templates.
     * Returned variables are sorted be name.
     * @see \Oro\Bundle\EntityBundle\Twig\Sandbox\SystemVariablesProviderInterface::getVariableDefinitions
     */
    public function getSystemVariableDefinitions(): array
    {
        $result = [];
        foreach ($this->systemProviders as $providerName) {
            $this->mergeVariableDefinitions(
                $result,
                $this->getSystemVariablesProvider($providerName)->getVariableDefinitions()
            );
        }
        ksort($result);

        return $result;
    }

    /**
     * Gets entity related variables available in sandboxed TWIG templates.
     * Returned variables are sorted by name.
     * @see \Oro\Bundle\EntityBundle\Twig\Sandbox\EntityVariablesProviderInterface::getVariableDefinitions
     */
    public function getEntityVariableDefinitions(): array
    {
        $result = [];
        foreach ($this->entityProviders as $providerName) {
            $allVariables = $this->getEntityVariablesProvider($providerName)->getVariableDefinitions();
            foreach ($allVariables as $className => $variables) {
                if (!isset($result[$className])) {
                    $result[$className] = [];
                }
                $this->mergeVariableDefinitions($result[$className], $variables);
            }
        }
        foreach ($result as &$variables) {
            ksort($variables);
        }

        return $result;
    }

    /**
     * Gets values of system variables available in sandboxed TWIG templates.
     * @see \Oro\Bundle\EntityBundle\Twig\Sandbox\SystemVariablesProviderInterface::getVariableValues
     */
    public function getSystemVariableValues(): array
    {
        $result = [];
        foreach ($this->systemProviders as $providerName) {
            $variables = $this->getSystemVariablesProvider($providerName)->getVariableValues();
            foreach ($variables as $name => $value) {
                $result[$name] = $value;
            }
        }

        return $result;
    }

    /**
     * Gets processors for entity related variables available in sandboxed TWIG templates.
     * @see \Oro\Bundle\EntityBundle\Twig\Sandbox\EntityVariablesProviderInterface::getVariableProcessors
     */
    public function getEntityVariableProcessors(string $entityClass): array
    {
        $result = [];
        foreach ($this->entityProviders as $providerName) {
            $processors = $this->getEntityVariablesProvider($providerName)->getVariableProcessors($entityClass);
            foreach ($processors as $name => $processor) {
                $result[$name] = $processor;
            }
        }

        return $result;
    }

    /**
     * Gets getters of entity related variables available in sandboxed TWIG templates.
     * @see \Oro\Bundle\EntityBundle\Twig\Sandbox\EntityVariablesProviderInterface::getVariableGetters
     */
    public function getEntityVariableGetters(): array
    {
        $result = [];
        foreach ($this->entityProviders as $providerName) {
            $allGetters = $this->getEntityVariablesProvider($providerName)->getVariableGetters();
            foreach ($allGetters as $className => $getters) {
                if (!isset($result[$className])) {
                    $result[$className] = [];
                }
                $this->mergeVariableGetters($result[$className], $getters);
            }
        }

        return $result;
    }

    private function getSystemVariablesProvider(string $name): SystemVariablesProviderInterface
    {
        return $this->providers->get($name);
    }

    private function getEntityVariablesProvider(string $name): EntityVariablesProviderInterface
    {
        return $this->providers->get($name);
    }

    /**
     * @param array $result    [variable name => [attribute name => attribute value, ...], ...]
     * @param array $variables [variable name => [attribute name => attribute value, ...], ...]
     */
    private function mergeVariableDefinitions(array &$result, array $variables): void
    {
        foreach ($variables as $name => $definition) {
            foreach ($definition as $attrName => $attrValue) {
                $result[$name][$attrName] = $attrValue;
            }
        }
    }

    /**
     * @param array $result  [variable name => string or NULL or [attribute name => attribute value, ...], ...]
     * @param array $getters [variable name => string or NULL or [attribute name => attribute value, ...], ...]
     */
    private function mergeVariableGetters(array &$result, array $getters): void
    {
        foreach ($getters as $name => $getter) {
            if (is_array($getter)) {
                if (array_key_exists($name, $result) && !is_array($result[$name])) {
                    $result[$name] = ['property_path' => $result[$name]];
                }
                foreach ($getter as $attrName => $attrValue) {
                    $result[$name][$attrName] = $attrValue;
                }
            } else {
                $result[$name] = $getter;
            }
        }
    }
}
