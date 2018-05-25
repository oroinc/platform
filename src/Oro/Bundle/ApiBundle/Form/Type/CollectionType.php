<?php

namespace Oro\Bundle\ApiBundle\Form\Type;

use Oro\Bundle\ApiBundle\Form\EventListener\CollectionEntryFactory;
use Oro\Bundle\ApiBundle\Form\EventListener\CollectionListener;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for a collection of objects.
 */
class CollectionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(
            new CollectionListener(
                new CollectionEntryFactory(
                    $options['entry_data_class'],
                    $options['entry_type'],
                    $options['entry_options']
                )
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'by_reference'  => false,
            'entry_options' => [],
        ]);
        $resolver->setRequired([
            'entry_type',
            'entry_data_class'
        ]);
    }
}
