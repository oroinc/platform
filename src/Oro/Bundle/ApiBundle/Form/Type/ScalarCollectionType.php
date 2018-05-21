<?php

namespace Oro\Bundle\ApiBundle\Form\Type;

use Oro\Bundle\ApiBundle\Form\EventListener\CollectionListener;
use Oro\Bundle\ApiBundle\Form\EventListener\ScalarCollectionEntryFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for a collection of objects.
 * Usually this form type is used if an association should be represented as a field in Data API.
 * @see \Oro\Bundle\ApiBundle\Request\DataType::isAssociationAsField
 */
class ScalarCollectionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(
            new CollectionListener(
                new ScalarCollectionEntryFactory(
                    $options['entry_data_class'],
                    $options['entry_data_property'],
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
            'entry_type'    => TextType::class,
            'entry_options' => []
        ]);
        $resolver->setRequired([
            'entry_data_class',
            'entry_data_property'
        ]);
    }
}
