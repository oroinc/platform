<?php

namespace Oro\Bundle\ThemeBundle\Form\Type;

use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration;
use Oro\Bundle\ThemeBundle\Exception\ConfigurationBuilderNotFoundException;
use Oro\Bundle\ThemeBundle\Form\Provider\ConfigurationBuildersProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
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
     * @throws ConfigurationBuilderNotFoundException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $configuration = $this->getThemeConfiguration($options);
        foreach ($configuration as $option) {
            $this->configurationBuildersProvider
                ->getConfigurationBuilderByOption($option)
                ->buildOption($builder, $option);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'theme_configuration' => [],
            'allow_extra_fields' => true
        ]);

        $resolver->setAllowedTypes('theme_configuration', 'array');
    }

    /**
     * {@inheritdoc}
     * @throws ConfigurationBuilderNotFoundException
     */
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $configuration = $this->getThemeConfiguration($options);
        $optionPrefix = sprintf('%s_%s', $this->getBlockPrefix(), 'item');
        foreach ($view->children as $name => $childView) {
            $childView->vars['block_prefixes'][] = $optionPrefix;

            $childForm = $form->get($name);
            $childOptions = $childForm->getConfig()->getOptions();
            $themeOption = $configuration[$name] ?? [];

            $this->configurationBuildersProvider
                ->getConfigurationBuilderByOption($themeOption)
                ->finishView($childView, $childForm, $childOptions, $themeOption);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_theme_configuration_list';
    }

    private function getThemeConfiguration(array $options): array
    {
        $themeConfiguration = [];
        $configuration = $options['theme_configuration'];
        foreach ($configuration['sections'] ?? [] as $sKey => $section) {
            foreach ($section['options'] ?? [] as $oKey => $option) {
                $option['name'] = ThemeConfiguration::buildOptionKey($sKey, $oKey);
                $themeConfiguration[$option['name']] = $option;
            }
        }

        return $themeConfiguration;
    }
}
