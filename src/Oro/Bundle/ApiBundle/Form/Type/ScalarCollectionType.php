<?php

namespace Oro\Bundle\ApiBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ApiBundle\Form\EventListener\CollectionListener;
use Oro\Bundle\ApiBundle\Form\EventListener\ScalarCollectionEntryFactory;

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
            'entry_type'    => 'text',
            'entry_options' => []
        ]);
        $resolver->setRequired([
            'entry_data_class',
            'entry_data_property'
        ]);
    }

    /**
     * {@inheritdoc}
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
        return 'oro_api_scalar_collection';
    }
}
