<?php

namespace Oro\Bundle\FormBundle\ImportExport\Serializer\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\FormBundle\Entity\PrimaryItem;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\CollectionNormalizer;

class PrimaryItemCollectionNormalizer extends CollectionNormalizer
{
    const PRIMARY_ITEM_TYPE = PrimaryItem::class;

    /**
     * Returned normalized data where first element is primary
     *
     * @param Collection $object object to normalize
     * @param mixed $format
     * @param array $context
     * @return array
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        $result = array();

        /** @var $item PrimaryItem */
        foreach ($object as $item) {
            $serializedItem = $this->serializer->normalize($item, $format, $context);
            if ($item->isPrimary()) {
                array_unshift($result, $serializedItem);
            } else {
                $result[] = $serializedItem;
            }
        }

        return $result;
    }

    /**
     * Denormalizes and sets primary to first element
     *
     * @param mixed $data
     * @param string $type
     * @param null $format
     * @param array $context
     *
     * @return ArrayCollection
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $result = parent::denormalize($data, $type, $format, $context);
        $primary = true;
        /** @var $item PrimaryItem */
        foreach ($result as $item) {
            $item->setPrimary($primary);
            $primary = false;
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        if ($data instanceof Collection && !$data->isEmpty()) {
            foreach ($data as $item) {
                if (!$item instanceof PrimaryItem) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = array()): bool
    {
        $itemType = $this->getItemType($type);
        if ($itemType && class_exists($itemType)) {
            return in_array(self::PRIMARY_ITEM_TYPE, class_implements($itemType));
        }
        return false;
    }
}
