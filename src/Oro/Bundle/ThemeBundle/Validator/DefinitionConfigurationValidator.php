<?php

namespace Oro\Bundle\ThemeBundle\Validator;

use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration;
use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Config\Loader\CumulativeConfigProcessorUtil;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * Theme configuration validator service.
 *
 * Service validates definition of theme.yml configuration.
 * */
class DefinitionConfigurationValidator implements ConfigurationValidatorInterface
{
    public function __construct(
        private ThemeConfiguration $configuration
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function supports(CumulativeResourceInfo $resource): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function validate(iterable $resources): iterable
    {
        $configs = [];
        $messages = [];

        foreach ($resources as $resource) {
            $themeName = basename(dirname($resource->path));
            $configs[] = [$themeName => $resource->data];
        }

        try {
            CumulativeConfigProcessorUtil::processConfiguration(
                'Resources/views/layouts/*/theme.yml',
                $this->configuration,
                $configs
            );
        } catch (InvalidConfigurationException $exception) {
            $messages[] = $exception->getMessage();
        }

        return $messages;
    }
}
