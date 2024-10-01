<?php

namespace Oro\Bundle\AddressBundle\Form\Type;

use Oro\Bundle\AddressBundle\Form\EventListener\ItemIdentifierCollectionTypeSubscriber;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class EmailCollectionType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new ItemIdentifierCollectionTypeSubscriber());
    }

    #[\Override]
    public function getParent(): ?string
    {
        return CollectionType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_email_collection';
    }
}
