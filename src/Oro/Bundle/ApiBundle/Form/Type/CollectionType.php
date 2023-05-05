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
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
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
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('by_reference', false)
            ->setDefault('entry_options', [])
            ->setRequired(['entry_type', 'entry_data_class']);
    }
}
