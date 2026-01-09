<?php

namespace Oro\Bundle\ThemeBundle\Form\EventListener;

use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration as LayoutThemeConfiguration;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;
use Oro\Component\Layout\Extension\Theme\Model\ThemeDefinitionBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Adds configuration section for ThemeConfiguration form.
 */
class ThemeConfigurationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ThemeDefinitionBagInterface $provider
    ) {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::SUBMIT       => 'onSubmit',
        ];
    }

    public function preSetData(FormEvent $event): void
    {
        $configuration = $this->getConfiguration($event);

        if ($event->getData()?->getId()) {
            FormUtils::replaceField($event->getForm(), 'theme', ['disabled' => true]);
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
            if (
                isset($configuration['sections'][$sKey]['options']) &&
                array_key_exists($oKey, $configuration['sections'][$sKey]['options'])
            ) {
                continue;
            }

            $themeConfiguration->removeConfigurationOption($key);
        }
    }

    private function getConfiguration(FormEvent $event): array
    {
        /** @var ThemeConfiguration $themeConfiguration */
        $themeConfiguration = $event->getData();
        $choices = $event->getForm()->get('theme')->getConfig()->getOption('choices');
        $themeName = $themeConfiguration->getTheme() ?? reset($choices);

        $themeDefinition = $this->provider->getThemeDefinition($themeName);

        return $themeDefinition['configuration'] ?? [];
    }
}
