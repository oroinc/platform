<?php

namespace Oro\Bundle\AddressBundle\Form\Type;

use Oro\Bundle\AddressBundle\Form\EventListener\ItemIdentifierCollectionTypeSubscriber;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Provides a form type for managing collections of phone numbers.
 *
 * This form type extends the base collection type to handle multiple phone number entries
 * with automatic identifier management. It integrates an event subscriber to ensure
 * proper handling of item identifiers within the collection, making it suitable for
 * forms that need to manage multiple phone numbers with unique identification.
 */
class PhoneCollectionType extends AbstractType
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
        return 'oro_phone_collection';
    }
}
