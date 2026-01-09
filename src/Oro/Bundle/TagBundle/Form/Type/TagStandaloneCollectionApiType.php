<?php

namespace Oro\Bundle\TagBundle\Form\Type;

use Oro\Bundle\SoapBundle\Form\Type\AbstractPatchableApiType;

/**
 * Patchable API form type for standalone tag entity collections.
 *
 * This form type extends the patchable API type to provide PATCH request support for standalone tag collections.
 * It inherits collection handling from {@see TagCollectionApiType} while adding support for partial updates,
 * making it suitable for API endpoints that need to support both full and partial tag collection modifications.
 */
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
