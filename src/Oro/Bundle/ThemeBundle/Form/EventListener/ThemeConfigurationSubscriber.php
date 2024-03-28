<?php

namespace Oro\Bundle\ThemeBundle\Form\EventListener;

use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration as LayoutThemeConfiguration;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfigurationProvider;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Adds configuration section for ThemeConfiguration form.
 */
class ThemeConfigurationSubscriber implements EventSubscriberInterface
{
    /** @var array['option-key' => 'value'] */
    private array $configuration = [];

    public function __construct(
        private ThemeConfigurationProvider $provider
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::SUBMIT       => 'onSubmit',
        ];
    }

    public function preSetData(FormEvent $event): void
    {
        /** @var ThemeConfiguration $themeConfiguration */
        $themeConfiguration = $event->getData();
        $configuration = $this->getConfiguration($event);

        // Replace theme.yml options value from ThemeConfiguration
        if ($themeConfiguration->getId()) {
            foreach ($configuration['sections'] ?? [] as $sKey => $section) {
                foreach ($section['options'] ?? [] as $oKey => $option) {
                    $value = $themeConfiguration->getConfigurationOption(
                        LayoutThemeConfiguration::buildOptionKey($sKey, $oKey)
                    );
                    $configuration['sections'][$sKey]['options'][$oKey]['default'] = $value;
                }
            }
        }

        FormUtils::replaceField($event->getForm(), 'configuration', ['theme_configuration' => $configuration]);
    }

    public function onSubmit(FormEvent $event): void
    {
        /** @var ThemeConfiguration $themeConfiguration */
        $themeConfiguration = $event->getData();
        $configuration = $this->getConfiguration($event);

        // Remove outdated options from ThemeConfiguration
        foreach ($themeConfiguration->getConfiguration() as $key => $value) {
            list($sKey, $oKey) = explode(LayoutThemeConfiguration::OPTION_KEY_DELIMITER, $key);
            if (isset($configuration['sections'][$sKey]['options'][$oKey])) {
                continue;
            }

            $themeConfiguration->removeConfigurationOption($key);
        }
    }

    private function getConfiguration(FormEvent $event): array
    {
        if ($this->configuration) {
            return $this->configuration;
        }

        /** @var ThemeConfiguration $themeConfiguration */
        $themeConfiguration = $event->getData();
        $choices = $event->getForm()->get('theme')->getConfig()->getOption('choices');
        $themeName = $themeConfiguration->getTheme() ?? reset($choices);

        $themeDefinition = $this->provider->getThemeDefinition($themeName);
        $this->configuration = $themeDefinition['configuration'] ?? [];

        return $this->configuration;
    }
}
