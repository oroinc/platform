<?php

namespace Oro\Bundle\ApiBundle\Normalizer;

class ObjectNormalizerRegistry
{
    /** @var array */
    private $normalizers = [];

    /** @var ObjectNormalizerInterface[]|null */
    private $sortedNormalizers;

    /**
     * Registers a normalizer for a specific object type
     *
     * @param ObjectNormalizerInterface $normalizer
     * @param int                       $priority
     */
    public function addNormalizer(ObjectNormalizerInterface $normalizer, $priority = 0)
    {
        $this->normalizers[$priority][] = $normalizer;
        $this->sortedNormalizers = null;
    }

    /**
     * Gets a normalizer for a given object
     *
     * @param object $object
     *
     * @return ObjectNormalizerInterface|null
     */
    public function getObjectNormalizer($object)
    {
        if (null === $this->sortedNormalizers) {
            krsort($this->normalizers);
            $this->sortedNormalizers = call_user_func_array('array_merge', $this->normalizers);
        }

        foreach ($this->sortedNormalizers as $normalizer) {
            if ($normalizer->supports($object)) {
                return $normalizer;
            }
        }

        return null;
    }
}
