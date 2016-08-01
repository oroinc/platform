<?php

namespace Oro\Bundle\ChartBundle\Form\Type;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\ChartBundle\Exception\InvalidArgumentException;

class ChartType extends ConfigProviderAwareType
{
    /**
     * @var array
     */
    protected $optionsGroups = ['settings', 'data_schema'];

    /**
     * @var EventSubscriberInterface
     */
    protected $eventListener;

    /**
     * @param EventSubscriberInterface $eventListener
     */
    public function setEventListener(EventSubscriberInterface $eventListener)
    {
        $this->eventListener = $eventListener;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->eventListener);

        $chartConfigs = $this->getChartConfigs($options);

        $builder
            ->add(
                'name',
                'choice',
                [
                    'label' => 'oro.chart.form.name.label',
                    'choices' => array_map(
                        function (array $chartConfig) {
                            return $chartConfig['label'];
                        },
                        $chartConfigs
                    ),
                    'empty_value' => 'oro.chart.form.chart_empty_value'
                ]
            )
            ->add(
                'settings',
                'oro_chart_settings_collection',
                [
                    'chart_configs' => $chartConfigs
                ]
            );
    }

    /**
     * @param array $options
     * @return array
     */
    protected function getChartConfigs(array $options)
    {
        $result = $this->configProvider->getChartConfigs();

        if (isset($options['chart_filter'])) {
            $result = array_filter($result, $options['chart_filter']);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(['chart_filter']);
        $resolver->setAllowedTypes(['chart_filter' => 'callable']);
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
    public function getBlockPrefix()
    {
        return 'oro_chart';
    }
}
