<?php

namespace Oro\Bundle\MicrosoftIntegrationBundle\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Form\Type\ConfigCheckbox;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The checkbox form type to enable/disable synchronization with Microsoft Azure Application.
 */
class EnableConfigCheckboxType extends ConfigCheckbox
{
    public const NAME = 'oro_microsoft_integration_enable_config_checkbox';

    private ConfigManager $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setNormalizer('disabled', function (Options $options, $value) {
            return $this->isMicrosoftConnectionConfigured() ? $value : true;
        });
    }

    private function isMicrosoftConnectionConfigured(): bool
    {
        return
            $this->hasConfigValue('oro_microsoft_integration.client_id')
            && $this->hasConfigValue('oro_microsoft_integration.client_secret')
            && $this->hasConfigValue('oro_microsoft_integration.tenant');
    }

    private function hasConfigValue(string $name): bool
    {
        $value = $this->configManager->get($name);

        return !empty($value);
    }
}
