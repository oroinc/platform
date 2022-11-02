<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Oro\Bundle\IntegrationBundle\DependencyInjection\IntegrationConfiguration;
use Oro\Component\Config\Cache\PhpArrayConfigProvider;
use Oro\Component\Config\Loader\CumulativeConfigProcessorUtil;
use Oro\Component\Config\Loader\Factory\CumulativeConfigLoaderFactory;
use Oro\Component\Config\Resolver\ResolverInterface;
use Oro\Component\Config\ResourcesContainerInterface;

/**
 * The provider for configuration that is loaded from "Resources/config/oro/integrations.yml" files.
 */
class SettingsProvider extends PhpArrayConfigProvider
{
    private const CONFIG_FILE = 'Resources/config/oro/integrations.yml';

    private ResolverInterface $resolver;

    public function __construct(string $cacheFile, bool $debug, ResolverInterface $resolver)
    {
        parent::__construct($cacheFile, $debug);
        $this->resolver = $resolver;
    }

    /**
     * Gets form fields settings.
     *
     * @param string $name            The name of form settings
     * @param string $integrationType The integration type
     *
     * @return array
     */
    public function getFormSettings($name, $integrationType)
    {
        $result = [];

        $config = $this->doGetConfig();
        if (isset($config[IntegrationConfiguration::FORM_NODE][$name])) {
            $priorities = [];
            $formData = $config[IntegrationConfiguration::FORM_NODE][$name];
            foreach ($formData as $fieldName => $field) {
                $field = $this->resolver->resolve($field, ['channelType' => $integrationType]);

                // if applicable node not set, then applicable to all
                if ($this->isApplicable($field, $integrationType)) {
                    $priorities[] = $field['priority'] ?? 0;
                    $result[$fieldName] = $field;
                }
            }

            \array_multisort($priorities, SORT_ASC, $result);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function doLoadConfig(ResourcesContainerInterface $resourcesContainer)
    {
        $configs = [];
        $configLoader = CumulativeConfigLoaderFactory::create('oro_integration_settings', self::CONFIG_FILE);
        $resources = $configLoader->load($resourcesContainer);
        foreach ($resources as $resource) {
            if (!empty($resource->data[IntegrationConfiguration::ROOT_NODE])) {
                $configs[] = $resource->data[IntegrationConfiguration::ROOT_NODE];
            }
        }

        return CumulativeConfigProcessorUtil::processConfiguration(
            self::CONFIG_FILE,
            new IntegrationConfiguration(),
            $configs
        );
    }

    /**
     * Checks whether field applicable for given integration type.
     *
     * If applicable option no set than applicable to all types.
     * Also if there is 'true' value it means that resolver function
     * returned true and it's applicable for this integration type
     *
     * @param array  $field
     * @param string $integrationType
     *
     * @return bool
     */
    private function isApplicable($field, $integrationType)
    {
        return
            empty($field['applicable'])
            || \in_array(true, $field['applicable'], true)
            || \in_array($integrationType, $field['applicable'], true);
    }
}
