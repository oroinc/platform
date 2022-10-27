<?php

namespace  Oro\Component\Action\Model;

use Oro\Bundle\ActionBundle\Model\ParameterInterface;
use Oro\Component\Action\Exception\AssemblerException;
use Oro\Component\ConfigExpression\ConfigurationPass\ConfigurationPassInterface;

/**
 * The base class for action assemblers.
 */
abstract class AbstractAssembler
{
    /**
     * @var ConfigurationPassInterface[]
     */
    protected $configurationPasses = array();

    public function addConfigurationPass(ConfigurationPassInterface $configurationPass)
    {
        $this->configurationPasses[] = $configurationPass;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function passConfiguration(array $data)
    {
        foreach ($this->configurationPasses as $configurationPass) {
            $data = $configurationPass->passConfiguration($data);
        }
        return $data;
    }

    /**
     * Get entity type
     *
     * @param array $configuration
     * @return string
     */
    protected function getEntityType(array $configuration)
    {
        $keys = array_keys($configuration);
        return $keys[0];
    }

    /**
     * Get entity parameters
     *
     * @param array $configuration
     * @return mixed
     */
    protected function getEntityParameters(array $configuration)
    {
        $values = array_values($configuration);
        return $values[0];
    }

    /**
     * Check that configuration is an entity configuration
     *
     * @param mixed $configuration
     * @return bool
     */
    protected function isService($configuration)
    {
        if (!\is_array($configuration) || count($configuration) !== 1) {
            return false;
        }

        return str_starts_with($this->getEntityType($configuration), '@');
    }

    /**
     * Get name of service referenced to $entityType
     *
     * @param string $entityType
     * @return bool
     */
    protected function getServiceName($entityType)
    {
        return substr($entityType, 1);
    }

    /**
     * @throws AssemblerException
     */
    protected function assertOptions(array $options, array $requiredOptions)
    {
        foreach ($requiredOptions as $optionName) {
            if (empty($options[$optionName])) {
                throw new AssemblerException(sprintf('Option "%s" is required', $optionName));
            }
        }
    }

    /**
     * @param array $options
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getOption(array $options, $key, $default = null)
    {
        if (isset($options[$key])) {
            return $options[$key];
        }
        return $default;
    }

    /**
     * @throws AssemblerException If parameter is invalid
     */
    protected function assertParameterHasOptions(ParameterInterface $parameter, array $optionNames)
    {
        foreach ($optionNames as $optionName) {
            if (!$parameter->hasOption($optionName)) {
                throw new AssemblerException(
                    sprintf(
                        'Option "%s" is required in %s "%s"',
                        $optionName,
                        $parameter->getInternalType(),
                        $parameter->getName()
                    )
                );
            }
        }
    }

    /**
     * @throws AssemblerException
     */
    protected function assertParameterHasNoOptions(ParameterInterface $parameter, array $optionNames)
    {
        foreach ($optionNames as $optionName) {
            if ($parameter->hasOption($optionName)) {
                throw new AssemblerException(
                    sprintf(
                        'Option "%s" cannot be used in %s "%s"',
                        $optionName,
                        $parameter->getInternalType(),
                        $parameter->getName()
                    )
                );
            }
        }
    }

    /**
     * @throws AssemblerException
     */
    protected function assertParameterHasClassOption(ParameterInterface $parameter)
    {
        $this->assertParameterHasOptions($parameter, ['class']);

        if (!class_exists($parameter->getOption('class'))) {
            throw new AssemblerException(
                sprintf(
                    'Class "%s" referenced by "class" option in %s "%s" not found',
                    $parameter->getOption('class'),
                    $parameter->getInternalType(),
                    $parameter->getName()
                )
            );
        }
    }
}
