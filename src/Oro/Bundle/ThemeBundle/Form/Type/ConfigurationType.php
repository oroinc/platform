<?php

namespace Oro\Bundle\ThemeBundle\Form\Type;

use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration;
use Oro\Bundle\ThemeBundle\Form\Provider\ConfigurationBuildersProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * FormType for displaying theme configurations section
 */
class ConfigurationType extends AbstractType
{
    public function __construct(
        private ConfigurationBuildersProvider $configurationBuildersProvider
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $configuration = $options['theme_configuration'];
        foreach ($configuration['sections'] ?? [] as $sKey => $section) {
            foreach ($section['options'] ?? [] as $oKey => $option) {
                $option['name'] = ThemeConfiguration::buildOptionKey($sKey, $oKey);
                $this->configurationBuildersProvider->getConfigurationBuilderByOption($option)->buildOption(
                    $builder,
                    $option
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'theme_configuration' => [],
                'allow_extra_fields' => true,
            ]
        );

        $resolver->setAllowedTypes('theme_configuration', 'array');
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_theme_configuration_list';
    }
}
