<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Oro\Bundle\IntegrationBundle\DependencyInjection\IntegrationConfiguration;
use Oro\Component\Config\Resolver\ResolverInterface;

class SettingsProvider
{
    /** @var array */
    protected $settings = [];

    /** @var ResolverInterface */
    protected $resolver;

    /**
     * @param array             $settings
     * @param ResolverInterface $resolver
     */
    public function __construct(array $settings, ResolverInterface $resolver)
    {
        $this->settings = $settings;
        $this->resolver = $resolver;
    }

    /**
     * Get form fields settings
     *
     * @param string $name            - node name that specifies which form settings needed
     * @param string $integrationType - integration type name for applicable check
     *
     * @throws \LogicException
     * @return array
     */
    public function getFormSettings($name, $integrationType)
    {
        $result = $priorities = [];

        if (isset($this->settings[IntegrationConfiguration::FORM_NODE_NAME][$name])) {
            $formData = $this->settings[IntegrationConfiguration::FORM_NODE_NAME][$name];

            foreach ($formData as $fieldName => $field) {
                $field = $this->resolver->resolve($field, ['channelType' => $integrationType]);

                // if applicable node not set, then applicable to all
                if ($this->isApplicable($field, $integrationType)) {
                    $priority           = isset($field['priority']) ? $field['priority'] : 0;
                    $priorities[]       = $priority;
                    $result[$fieldName] = $field;
                }
            }

            array_multisort($priorities, SORT_ASC, $result);
        }

        return $result;
    }

    /**
     * Check whether field applicable for given integration type
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
    protected function isApplicable($field, $integrationType)
    {
        return
            empty($field['applicable'])
            || in_array(true, $field['applicable'], true)
            || in_array($integrationType, $field['applicable'], true);
    }
}
