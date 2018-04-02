<?php

namespace Oro\Bundle\ReportBundle\Form\Type;

use Oro\Bundle\QueryDesignerBundle\Form\Type\AbstractQueryDesignerType;
use Oro\Bundle\ReportBundle\Form\EventListener\DateGroupingFormSubscriber;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReportType extends AbstractQueryDesignerType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text', array('required' => true))
            ->add('entity', ReportEntityChoiceType::class, array('required' => true))
            ->add(
                'type',
                EntityType::class,
                array(
                    'class'       => 'OroReportBundle:ReportType',
                    'choice_label'    => 'label',
                    'required'    => true,
                    'placeholder' => 'oro.report.form.choose_report_type'
                )
            )
            ->add(
                'hasChart',
                'checkbox',
                array(
                    'mapped'   => false,
                    'required' => false,
                )
            )
            ->add(
                'chartOptions',
                ReportChartType::class,
                array('required' => true)
            )
            ->add('description', 'textarea', array('required' => false));

        parent::buildForm($builder, $options);
        $builder->addEventSubscriber(new DateGroupingFormSubscriber());
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $options = array_merge(
            $this->getDefaultOptions(),
            array(
                'data_class'         => 'Oro\Bundle\ReportBundle\Entity\Report',
                'csrf_token_id'      => 'report',
                'query_type'         => 'report',
            )
        );

        $resolver->setDefaults($options);
    }

    /**
     * {@inheritdoc}
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
        return 'oro_report';
    }
}
