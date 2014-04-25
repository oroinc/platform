<?php

namespace Oro\Bundle\ReportBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\ChartBundle\Model\ConfigProvider;

class ReportChartChoiceType extends AbstractType
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
        //todo: add fields here use config provider to get data schema
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_chart';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_report_chart_choice';
    }
}
