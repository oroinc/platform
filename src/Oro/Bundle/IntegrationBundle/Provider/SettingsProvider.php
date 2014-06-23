<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Oro\Component\Config\Resolver\ResolverInterface;

use Oro\Bundle\IntegrationBundle\DependencyInjection\IntegrationConfiguration;

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
     * @param string $name        - node name that specifies which form settings needed
     * @param string $channelType - channel type name for applicable check
     *
     * @throws \LogicException
     * @return array
     */
    public function getFormSettings($name, $channelType)
    {
        if (!isset(
            $this->settings[IntegrationConfiguration::FORM_NODE_NAME],
            $this->settings[IntegrationConfiguration::FORM_NODE_NAME][$name])
        ) {
            throw new \LogicException('Form settings not found');
        }

        $formData = $this->settings[IntegrationConfiguration::FORM_NODE_NAME][$name];

        $result = $priorities = [];
        foreach ($formData as $fieldName => $field) {
            $field = $this->resolver->resolve($field, ['channelType' => $channelType]);

            // if applicable node not set, then applicable to all
            if ($this->isApplicable($field, $channelType)) {
                $priority           = isset($field['priority']) ? $field['priority'] : 0;
                $priorities[]       = $priority;
                $result[$fieldName] = $field;
            }
        }

        array_multisort($priorities, SORT_ASC, $result);

        return $result;
    }

    /**
     * Check whether field applicable for given channel type
     *
     * If applicable option no set than applicable to all types.
     * Also if there is 'true' value it means that resolver function
     * returned true and it's applicable for this channel type
     *
     * @param array  $field
     * @param string $channelType
     *
     * @return bool
     */
    protected function isApplicable($field, $channelType)
    {
        return
            empty($field['applicable'])
            || in_array(true, $field['applicable'], true)
            || in_array($channelType, $field['applicable'], true);
    }
}
