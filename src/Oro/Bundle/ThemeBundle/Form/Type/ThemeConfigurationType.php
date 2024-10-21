<?php

namespace Oro\Bundle\ThemeBundle\Form\Type;

use Oro\Bundle\FrontendBundle\Form\Type\ThemeSelectType;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;
use Oro\Bundle\ThemeBundle\Form\EventListener\ThemeConfigurationSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for ThemeConfiguration entity.
 */
class ThemeConfigurationType extends AbstractType
{
    public function __construct(
        private ThemeConfigurationSubscriber $themeConfigurationSubscriber
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber($this->themeConfigurationSubscriber);

        $builder
            ->add(
                'name',
                TextType::class,
                [
                    'label' => 'oro.theme.themeconfiguration.name.label',
                    'required' => true,
                ]
            )
            ->add(
                'description',
                TextareaType::class,
                [
                    'label'    => 'oro.theme.themeconfiguration.description.label',
                    'required' => false,
                ]
            )
            ->add(
                'theme',
                ThemeSelectType::class,
                [
                    'label' => 'oro.theme.themeconfiguration.theme.label',
                    'required' => true,
                    'attr' => [
                        'data-role' => 'dynamic-render'
                    ]
                ]
            )
            ->add(
                'configuration',
                ConfigurationType::class,
                [
                    'label'    => 'oro.theme.themeconfiguration.configuration.label',
                    'required' => true,
                ]
            );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => ThemeConfiguration::class
            ]
        );
    }
}
