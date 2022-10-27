<?php

namespace Oro\Bundle\CacheBundle\Generator;

use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * This converter uses symfony's serializer component to prepare a string from the provided object.
 */
class ObjectCacheDataSerializer implements ObjectCacheDataConverterInterface
{
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToString($object, string $scope): string
    {
        return $this->serializer->serialize(
            $object,
            'json',
            [
                AbstractNormalizer::GROUPS => [$scope],
            ]
        );
    }
}
