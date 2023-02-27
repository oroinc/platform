<?php

namespace Oro\Bundle\ApiBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

/**
 * The form type for a collection of manageable entities.
 */
class EntityCollectionType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function getParent(): ?string
    {
        return CollectionType::class;
    }
}
