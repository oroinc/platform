<?php

namespace Oro\Bundle\ImportExportBundle\Serializer\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

class CollectionNormalizer implements
    SerializerAwareInterface,
    ContextAwareNormalizerInterface,
    ContextAwareDenormalizerInterface
{
    /**
     * @var SerializerInterface|ContextAwareNormalizerInterface|ContextAwareDenormalizerInterface
     */
    protected $serializer;

    /**
     * @throws InvalidArgumentException
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        if (!$serializer instanceof ContextAwareNormalizerInterface
            || !$serializer instanceof ContextAwareDenormalizerInterface) {
            throw new InvalidArgumentException(
                sprintf(
                    'Serializer must implement "%s" and "%s"',
                    ContextAwareNormalizerInterface::class,
                    ContextAwareDenormalizerInterface::class
                )
            );
        }
        $this->serializer = $serializer;
    }

    /**
     * Returned normalized data
     *
     * @param Collection $object object to normalize
     * @param mixed $format
     * @param array $context
     *
     * @return array
     */
    public function normalize($object, string $format = null, array $context = [])
    {
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
     * @param mixed $format
     * @param array $context
     *
     * @return ArrayCollection
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
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

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof Collection;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return (bool)preg_match(
            '/^(Doctrine\\\Common\\\Collections\\\ArrayCollection|ArrayCollection)(<[\w_<>\\\]+>)?$/',
            $type
        );
    }
}
