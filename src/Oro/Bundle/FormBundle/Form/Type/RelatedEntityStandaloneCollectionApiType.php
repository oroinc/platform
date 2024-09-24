<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\SoapBundle\Form\Type\AbstractPatchableApiType;

class RelatedEntityStandaloneCollectionApiType extends AbstractPatchableApiType
{
    #[\Override]
    public function getParent(): ?string
    {
        return RelatedEntityCollectionApiType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_related_entity_standalone_collection_api';
    }
}
