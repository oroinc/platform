<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RelatedEntityCollectionApiType extends AbstractType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'allow_add'          => true,
                'entry_type'         => RelatedEntityApiType::class
            ]
        );
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
        return 'oro_related_entity_collection_api';
    }
}
