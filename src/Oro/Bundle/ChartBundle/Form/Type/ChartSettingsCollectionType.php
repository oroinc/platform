<?php

namespace Oro\Bundle\ChartBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChartSettingsCollectionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($options['chart_configs'] as $chartName => $chartConfig) {
            $builder->add(
                $chartName,
                ChartSettingsType::class,
                [
                    'chart_name'   => $chartName,
                    'chart_config' => $chartConfig
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['chart_configs']);

        $resolver->setAllowedTypes('chart_configs', 'array');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_chart_settings_collection';
    }
}
