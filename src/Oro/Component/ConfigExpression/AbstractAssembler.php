<?php

namespace Oro\Component\ConfigExpression;

use Oro\Component\ConfigExpression\ConfigurationPass\ConfigurationPassInterface;

abstract class AbstractAssembler implements AssemblerInterface
{
    /** @var ConfigurationPassInterface[] */
    protected $configurationPasses = [];

    /**
     * Registers the configuration pass.
     *
     * @param ConfigurationPassInterface $configurationPass
     */
    public function addConfigurationPass(ConfigurationPassInterface $configurationPass)
    {
        $this->configurationPasses[] = $configurationPass;
    }

    /**
     * Applies all configuration passes.
     *
     * @param array $data
     *
     * @return array
     */
    protected function passConfiguration(array $data)
    {
        foreach ($this->configurationPasses as $pass) {
            $data = $pass->passConfiguration($data);
        }

        return $data;
    }

    /**
     * Gets the entity type.
     *
     * @param array $configuration
     *
     * @return string
     */
    protected function getEntityType(array $configuration)
    {
        reset($configuration);

        return key($configuration);
    }

    /**
     * Gets the entity parameters.
     *
     * @param array $configuration
     *
     * @return mixed
     */
    protected function getEntityParameters(array $configuration)
    {
        return reset($configuration);
    }

    /**
     * Checks whether the given configuration is an expression.
     *
     * @param mixed $configuration
     *
     * @return bool
     */
    protected function isExpression($configuration)
    {
        if (!is_array($configuration) || count($configuration) !== 1) {
            return false;
        }

        return strpos($this->getEntityType($configuration), '@') === 0;
    }

    /**
     * Gets the type of the given expression identifier.
     *
     * @param string $entityType
     *
     * @return string
     */
    protected function getExpressionType($entityType)
    {
        return substr($entityType, 1);
    }
}
