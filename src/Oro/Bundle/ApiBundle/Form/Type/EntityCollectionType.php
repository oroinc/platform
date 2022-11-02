<?php

namespace Oro\Bundle\ApiBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

/**
 * The form type for a collection of manageable entities.
 */
class EntityCollectionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CollectionType::class;
    }
}
