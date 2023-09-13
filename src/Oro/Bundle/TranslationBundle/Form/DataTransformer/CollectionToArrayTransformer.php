<?php

namespace Oro\Bundle\TranslationBundle\Form\DataTransformer;

use Symfony\Bridge\Doctrine\Form\DataTransformer\CollectionToArrayTransformer as DoctrineCollectionToArrayTransformer;

/**
 * Transforms a Doctrine Collection to an array.
 */
class CollectionToArrayTransformer extends DoctrineCollectionToArrayTransformer
{
    /**
     * {@inheritdoc}
     */
    public function transform($collection): mixed
    {
        // process any empty value (string, array)
        if (empty($collection)) {
            return array();
        }

        return parent::transform($collection);
    }
}
