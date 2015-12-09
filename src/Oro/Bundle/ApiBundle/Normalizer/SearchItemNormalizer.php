<?php

namespace Oro\Bundle\ApiBundle\Normalizer;

use Oro\Bundle\SearchBundle\Query\Result\Item as SearchResultItem;

class SearchItemNormalizer implements ObjectNormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports($object)
    {
        return $object instanceof SearchResultItem;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object)
    {
        /** @var SearchResultItem $object */

        return [
            'id'     => $object->getRecordId(),
            'entity' => $object->getEntityName(),
            'title'  => $object->getRecordTitle()
        ];
    }
}
