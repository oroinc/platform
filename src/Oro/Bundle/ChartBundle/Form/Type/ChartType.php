<?php

namespace Oro\Bundle\ChartBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\ChartBundle\Exception\InvalidArgumentException;

class ChartType extends ConfigProviderAwareType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
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

        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'submit']);
    }

    /**
     * @param FormEvent $event
     * @throws InvalidArgumentException
     */
    public function submit(FormEvent $event)
    {
        $formData = $event->getData();

        if (!isset($formData['type'])) {
            throw new InvalidArgumentException('Type data is missing');
        }

        $type = $formData['type'];

        if (isset($formData['settings'][$type])) {
            $formData['settings'] = $formData['settings'][$type];
        }

        if (isset($formData['data_schema'][$type])) {
            $formData['data_schema'] = $formData['data_schema'][$type];
        }

        $event->setData($formData);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_chart';
    }
}
