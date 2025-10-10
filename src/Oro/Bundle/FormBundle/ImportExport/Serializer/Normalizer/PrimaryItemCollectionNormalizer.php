<?php

namespace Oro\Bundle\FormBundle\ImportExport\Serializer\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\FormBundle\Entity\PrimaryItem;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\CollectionNormalizer;

/**
 * Normalizes PrimaryItem collections by placing primary items first
 * and managing primary status during serialization processes
 */
class PrimaryItemCollectionNormalizer extends CollectionNormalizer
{
    const PRIMARY_ITEM_TYPE = PrimaryItem::class;

    /**
     * Returned normalized data where first element is primary
     *
     * @param Collection $object object to normalize
     * @param string|null $format
     * @param array $context
     * @return array
     */
    #[\Override]
    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): float|int|bool|\ArrayObject|array|string|null {
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
     * @param string|null $format
     * @param array $context
     *
     * @return ArrayCollection
     */
    #[\Override]
    public function denormalize($data, string $type, ?string $format = null, array $context = []): mixed
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

    #[\Override]
    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
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

    #[\Override]
    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = array()): bool
    {
        $itemType = $this->getItemType($type);
        if ($itemType && class_exists($itemType)) {
            return in_array(self::PRIMARY_ITEM_TYPE, class_implements($itemType));
        }
        return false;
    }
}
