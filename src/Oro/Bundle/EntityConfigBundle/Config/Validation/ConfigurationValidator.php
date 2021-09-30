<?php

namespace Oro\Bundle\EntityConfigBundle\Config\Validation;

use Oro\Bundle\EntityConfigBundle\Exception\EntityConfigValidationException;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

/**
 * The service for config validation
 */
class ConfigurationValidator
{
    public const CONFIG_ENTITY_TYPE = 1;
    public const CONFIG_FIELD_TYPE = 2;

    /** @var ConfigurationManager */
    private ConfigurationManager $manager;

    /**
     * ConfigurationValidator constructor.
     * @param ConfigurationManager $manager
     */
    public function __construct(ConfigurationManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param int $type
     * @param string $sectionName
     * @param array $values
     */
    public function validate(int $type, string $sectionName, array $values)
    {
        $configuration = $this->manager->getConfiguration($type, $sectionName);
        $className = $this->manager->getClass($type, $sectionName);
        $configAttributeNames = $this->manager->getConfigAttributeNamesByType($type);
        if (!$configuration) {
            throw new EntityConfigValidationException(
                sprintf(
                    'The "%s" is not available entity config attribute. List of available: %s',
                    $sectionName,
                    implode(', ', $configAttributeNames)
                )
            );
        }
        try {
            $processor = new Processor();
            $processor->processConfiguration(
                $configuration,
                [$sectionName => $values]
            );
        } catch (InvalidConfigurationException $exception) {
            throw new EntityConfigValidationException(
                sprintf(
                    'Entity config validation error: %s, happens in the %s ',
                    $exception->getMessage(),
                    $className
                ),
                $exception->getCode(),
                $exception
            );
        }
    }
}
