<?php

namespace Oro\Bundle\ImportExportBundle\Serializer\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Serializer normalizer for handling Doctrine Collections during import/export operations
 */
class CollectionNormalizer implements
    SerializerAwareInterface,
    NormalizerInterface,
    DenormalizerInterface
{
    /**
     * @var SerializerInterface|NormalizerInterface|DenormalizerInterface
     */
    protected $serializer;

    /**
     * @throws InvalidArgumentException
     */
    #[\Override]
    public function setSerializer(SerializerInterface $serializer): void
    {
        if (!$serializer instanceof NormalizerInterface
            || !$serializer instanceof DenormalizerInterface) {
            throw new InvalidArgumentException(
                sprintf(
                    'Serializer must implement "%s" and "%s"',
                    NormalizerInterface::class,
                    DenormalizerInterface::class
                )
            );
        }
        $this->serializer = $serializer;
    }

    /**
     * Returned normalized data
     *
     * @param Collection $object object to normalize
     * @param string|null $format
     * @param array $context
     *
     * @return array
     */
    #[\Override]
    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): float|int|bool|\ArrayObject|array|string|null {
        $result = [];

        foreach ($object as $item) {
            $serializedItem = $this->serializer->normalize($item, $format, $context);
            $result[] = $serializedItem;
        }

        return $result;
    }

    /**
     * Returns collection of denormalized data
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
        if (!is_array($data)) {
            return new ArrayCollection();
        }
        $itemType = $this->getItemType($type);
        if (!$itemType) {
            return new ArrayCollection($data);
        }
        $result = new ArrayCollection();
        foreach ($data as $item) {
            $result->add($this->serializer->denormalize($item, $itemType, $format, $context));
        }

        return $result;
    }

    /**
     * @param string $class
     *
     * @return string|null
     */
    protected function getItemType($class)
    {
        $collectionRegexp = '/^(Doctrine\\\Common\\\Collections\\\ArrayCollection|ArrayCollection)(<([\w_<>\\\]+)>)$/';

        if (preg_match($collectionRegexp, $class, $matches)) {
            return $matches[3];
        }

        return null;
    }

    #[\Override]
    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Collection;
    }

    #[\Override]
    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        return (bool)preg_match(
            '/^(Doctrine\\\Common\\\Collections\\\ArrayCollection|ArrayCollection)(<[\w_<>\\\]+>)?$/',
            $type
        );
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['object' => true];
    }
}
