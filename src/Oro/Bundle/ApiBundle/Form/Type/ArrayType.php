<?php

namespace Oro\Bundle\ApiBundle\Form\Type;

use Oro\Bundle\ApiBundle\Form\DataTransformer\ArrayDataTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for array fields.
 */
class ArrayType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addViewTransformer(new ArrayDataTransformer());
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('compound', false)
            ->setDefault('multiple', true);
    }
}
