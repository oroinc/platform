<?php

namespace Oro\Bundle\ImportExportBundle\Serializer;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Serializer as SymfonySerializer;

/**
 * Serializes and deserializes data given by the import/export functionality.
 */
class Serializer extends SymfonySerializer implements SerializerInterface
{
    private const PROCESSOR_ALIAS_KEY = 'processorAlias';
    private const ENTITY_NAME_KEY = 'entityName';

    /** @var ContextAwareNormalizerInterface[]|ContextAwareDenormalizerInterface[] */
    protected array $normalizers = [];

    /** @var ContextAwareDenormalizerInterface[] */
    protected array $denormalizerCache = [];

    /** @var ContextAwareNormalizerInterface[] */
    protected array $normalizerCache = [];

    public function __construct(array $normalizers = [], array $encoders = [])
    {
        parent::__construct($normalizers, $encoders);

        $this->normalizers = $normalizers;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($data, string $format = null, array $context = [])
    {
        if (null === $data || is_scalar($data)) {
            return $data;
        }

        if (is_object($data) && $this->supportsNormalization($data, $format, $context)) {
            $this->cleanCacheIfDataIsCollection($data, $format, $context);

            return $this->normalizeObject($data, $format, $context);
        }

        if ($data instanceof \Traversable) {
            $normalized = [];
            foreach ($data as $key => $val) {
                $normalized[$key] = $this->normalize($val, $format, $context);
            }

            return $normalized;
        }

        if (is_array($data)) {
            foreach ($data as $key => $val) {
                $data[$key] = $this->normalize($val, $format, $context);
            }

            return $data;
        }

        throw new UnexpectedValueException(
            sprintf('An unexpected value could not be normalized: %s', var_export($data, true))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        if (!$this->normalizers) {
            throw new LogicException('You must register at least one normalizer to be able to denormalize objects.');
        }

        $cacheKey = $this->getCacheKey($type, $format, $context);

        if (isset($this->denormalizerCache[$cacheKey])) {
            $normalizer = $this->denormalizerCache[$cacheKey];

            return $normalizer->denormalize($data, $type, $format, $context);
        }

        foreach ($this->normalizers as $normalizer) {
            if ($normalizer instanceof ContextAwareDenormalizerInterface
                && $normalizer->supportsDenormalization($data, $type, $format, $context)) {
                $this->denormalizerCache[$cacheKey] = $normalizer;

                return $normalizer->denormalize($data, $type, $format, $context);
            }
        }

        throw new UnexpectedValueException(
            sprintf('Could not denormalize object of type %s, no supporting normalizer found.', $type)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        try {
            $this->getNormalizer($data, $format, $context);
        } catch (RuntimeException $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        try {
            $this->getDenormalizer($data, $type, $format, $context);
        } catch (RuntimeException $e) {
            return false;
        }

        return true;
    }

    protected function getCacheKey(string $type, string $format = null, array $context = []): string
    {
        $cacheKeyFields = [$type, $format];

        // Add context fields to cache key
        $contextFields = [
            self::PROCESSOR_ALIAS_KEY,
            self::ENTITY_NAME_KEY,
        ];
        $cacheKeyFields = array_merge($cacheKeyFields, array_intersect_key($context, array_flip($contextFields)));

        return md5(implode('', $cacheKeyFields));
    }

    protected function cleanCacheIfDataIsCollection(object $data, string $format = null, array $context = []): void
    {
        if ($data instanceof Collection) {
            $cacheKey = $this->getCacheKey(get_class($data), $format, $context);
            // Clear cache of normalizer for collections,
            // because of wrong behaviour when selecting normalizer for collections of elements with different types
            unset($this->normalizerCache[$cacheKey]);
        }
    }

    private function normalizeObject($object, string $format = null, array $context = [])
    {
        if (!$this->normalizers) {
            throw new LogicException('You must register at least one normalizer to be able to normalize objects.');
        }

        $class = get_class($object);

        $cacheKey = $this->getCacheKey($class, $format, $context);

        if (isset($this->normalizerCache[$cacheKey])) {
            $normalizer = $this->normalizerCache[$cacheKey];

            return $normalizer->normalize($object, $format, $context);
        }

        foreach ($this->normalizers as $normalizer) {
            if ($normalizer instanceof ContextAwareNormalizerInterface
                && $normalizer->supportsNormalization($object, $format, $context)) {
                $this->normalizerCache[$cacheKey] = $normalizer;

                return $normalizer->normalize($object, $format, $context);
            }
        }

        throw new UnexpectedValueException(
            sprintf('Could not normalize object of type %s, no supporting normalizer found.', $class)
        );
    }

    private function getNormalizer($data, string $format = null, array $context = []): ContextAwareNormalizerInterface
    {
        foreach ($this->normalizers as $normalizer) {
            if (!$normalizer instanceof ContextAwareNormalizerInterface) {
                continue;
            }

            if ($normalizer->supportsNormalization($data, $format, $context)) {
                return $normalizer;
            }
        }

        throw new RuntimeException(sprintf('No normalizer found for format "%s".', $format));
    }

    private function getDenormalizer(
        $data,
        string $type,
        string $format = null,
        array $context = []
    ): ContextAwareDenormalizerInterface {
        foreach ($this->normalizers as $normalizer) {
            if (!$normalizer instanceof ContextAwareDenormalizerInterface) {
                continue;
            }

            if ($normalizer->supportsDenormalization($data, $type, $format, $context)) {
                return $normalizer;
            }
        }

        throw new RuntimeException(sprintf('No denormalizer found for format "%s".', $format));
    }
}
