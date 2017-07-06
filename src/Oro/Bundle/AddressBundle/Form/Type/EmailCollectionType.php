<?php

namespace Oro\Bundle\AddressBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\AddressBundle\Form\EventListener\ItemIdentifierCollectionTypeSubscriber;

class EmailCollectionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new ItemIdentifierCollectionTypeSubscriber());
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_collection';
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
        return 'oro_email_collection';
    }
}
