<?php

namespace Oro\Bundle\ChartBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\ChartBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ChartBundle\Model\ConfigProvider;

class ChartType extends AbstractType
{
    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @param ConfigProvider $configProvider
     */
    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

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
            );

        foreach ($chartConfigs as $chartName => $chartConfig) {
            $builder->add(
                $chartName,
                'oro_chart_setting',
                [
                    'chart_name'   => $chartName,
                    'chart_config' => $chartConfig
                ]
            );
        }

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

        foreach ($formData as $key => $chartData) {
            if ($key !== $type) {
                unset($formData[$key]);
            }
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
