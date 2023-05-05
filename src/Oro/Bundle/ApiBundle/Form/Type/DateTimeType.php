<?php

namespace Oro\Bundle\ApiBundle\Form\Type;

use Oro\Bundle\ApiBundle\Form\DataTransformer\DateTimeToStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for datetime, date or time fields.
 */
class DateTimeType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addViewTransformer(new DateTimeToStringTransformer(
            $options['with_time'],
            $options['with_date']
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('with_date', true)
            ->setDefault('with_date', true)
            ->setDefault('with_time', true)
            ->setDefault('by_reference', false)
            ->setDefault('compound', false);
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_api_datetime';
    }
}
