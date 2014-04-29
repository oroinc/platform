<?php

namespace Oro\Bundle\ChartBundle\Form\Type;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormBuilderInterface;

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

        $chartConfigs = $this->configProvider->getChartConfigs();

        $builder
            ->add(
                'type',
                'choice',
                [
                    'choices' => array_map(
                        function (array $chartConfig) {
                            return $chartConfig['label'];
                        },
                        $chartConfigs
                    )
                ]
            )
            ->add('settings', 'oro_chart_settings_collection', ['chart_configs' => $chartConfigs]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_chart';
    }
}
