<?php

namespace Oro\Bundle\ThemeBundle\Validator;

use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfigurationProvider;
use Oro\Component\Config\ResourcesContainer;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * Validates that the configuration of themes valid.
 */
class ConfigurationValidator
{
    /**
     * @param ThemeConfigurationProvider        $configurationProvider
     * @param ConfigurationValidatorInterface[] $validators
     */
    public function __construct(
        private ThemeConfigurationProvider $configurationProvider,
        private iterable $validators
    ) {
    }

    /**
     * @return string[]
     */
    public function validate(): array
    {
        try {
            /** @var array $config */
            $config = $this->configurationProvider->loadConfig(new ResourcesContainer());
        } catch (InvalidConfigurationException $exception) {
            return  [$exception->getMessage()];
        }

        $validationMessages = [];
        foreach ($this->validators as $validator) {
            $messages = $validator->validate($config);
            foreach ($messages as $message) {
                $validationMessages[] = $message;
            }
        }

        return $validationMessages;
    }
}
