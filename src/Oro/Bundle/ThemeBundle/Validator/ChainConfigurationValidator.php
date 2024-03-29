<?php

namespace Oro\Bundle\ThemeBundle\Validator;

use Oro\Component\Config\ResourcesContainer;
use Oro\Component\Layout\Extension\Theme\Model\ThemeDefinitionBagInterface;

/**
 * Theme configuration validator service.
 *
 * Service validates theme configuration files theme.yml.
 * */
class ChainConfigurationValidator
{
    /**
     * @param ConfigurationValidatorInterface[] $validators
     */
    public function __construct(
        private ThemeDefinitionBagInterface $provider,
        private iterable $validators
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function validate(): iterable
    {
        $resourcesContainer = new ResourcesContainer();
        $resources = $this->provider->loadThemeResources($resourcesContainer);
        $messages = [];

        /** @var ConfigurationValidatorInterface $validator */
        foreach ($this->validators as $validator) {
            $supportedResources = [];
            foreach ($resources as $resource) {
                if ($validator->supports($resource)) {
                    $supportedResources[] = $resource;
                }
            }

            $validatorMessages = $validator->validate($supportedResources);
            $messages = array_merge($messages, $validatorMessages);
        }

        return $messages;
    }
}
