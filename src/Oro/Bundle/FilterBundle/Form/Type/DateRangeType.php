<?php

namespace Oro\Bundle\FilterBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FilterBundle\Form\EventListener\DateFilterSubscriber;

class DateRangeType extends AbstractType
{
    const NAME = 'oro_type_date_range';

    /** @var DateFilterSubscriber */
    protected $subscriber;

    public function __construct(DateFilterSubscriber $subscriber)
    {
        $this->subscriber = $subscriber;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'start',
            $options['field_type'],
            array_merge(
                array(
                    'required'       => false,
                    'widget'         => 'single_text',
                    'format'         => 'yyyy-MM-dd',
                    'model_timezone' => 'UTC',
                    'view_timezone'  => 'UTC',
                ),
                $options['field_options'],
                $options['start_field_options']
            )
        );

        $builder->add(
            'end',
            $options['field_type'],
            array_merge(
                array(
                    'required'       => false,
                    'widget'         => 'single_text',
                    'format'         => 'yyyy-MM-dd',
                    'model_timezone' => 'UTC',
                    'view_timezone'  => 'UTC',
                ),
                $options['field_options'],
                $options['end_field_options']
            )
        );

        $builder->addEventSubscriber($this->subscriber);
    }

    /**
     * {@inheritDoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $children                     = $form->all();
        $view->vars['value']['start'] = $children['start']->getViewData();
        $view->vars['value']['end']   = $children['end']->getViewData();
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'field_type'          => 'date',
                'field_options'       => array(),
                'start_field_options' => array(),
                'end_field_options'   => array(),
            )
        );
    }
}
