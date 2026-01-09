<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\SoapBundle\Form\Type\AbstractPatchableApiType;

/**
 * API form type for handling standalone collections of related entities.
 *
 * This type extends {@see RelatedEntityCollectionApiType} with support for `PATCH` requests,
 * allowing partial updates to collections of related entities in API endpoints.
 */
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
