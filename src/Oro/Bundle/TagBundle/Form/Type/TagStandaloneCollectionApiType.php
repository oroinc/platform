<?php

namespace Oro\Bundle\TagBundle\Form\Type;

use Oro\Bundle\SoapBundle\Form\Type\AbstractPatchableApiType;

class TagStandaloneCollectionApiType extends AbstractPatchableApiType
{
    #[\Override]
    public function getParent(): ?string
    {
        return TagCollectionApiType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_tag_standalone_collection_api';
    }
}
