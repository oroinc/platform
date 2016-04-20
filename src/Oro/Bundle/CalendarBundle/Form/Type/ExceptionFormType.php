<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExceptionFormType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'title',
                'text',
                [
                    'required' => true,
                ]
            )
            ->add(
                'description',
                'text',
                [
                    'required' => false,
                ]
            )
            ->add(
                'start',
                'datetime',
                [
                    'required' => true,
                    'with_seconds' => true,
                    'widget' => 'single_text',
                    'format' => DateTimeType::HTML5_FORMAT,
                    'model_timezone' => 'UTC',
                ]
            )
            ->add(
                'end',
                'datetime',
                [
                    'required' => true,
                    'with_seconds' => true,
                    'widget' => 'single_text',
                    'format' => DateTimeType::HTML5_FORMAT,
                    'model_timezone' => 'UTC',
                ]
            )
            ->add(
                'originalDate',
                'datetime',
                [
                    'required' => false,
                    'with_seconds' => true,
                    'widget' => 'single_text',
                    'format' => DateTimeType::HTML5_FORMAT,
                    'model_timezone' => 'UTC',
                ]
            )
            ->add(
                'allDay',
                'checkbox',
                [
                    'required' => false,
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'csrf_protection' => false,
                'data_class' => 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_calendar_exception';
    }
}
