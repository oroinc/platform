<?php

namespace Oro\Bundle\CacheBundle\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer as BaseGetSetMethodNormalizer;

/**
 * Modified normalizer to handle(skip) exception thrown during reading of object's attribute values.
 */
class GetSetMethodNormalizer extends BaseGetSetMethodNormalizer
{
    /**
     * {@inheritDoc}
     */
    protected function getAttributeValue(object $object, string $attribute, string $format = null, array $context = [])
    {
        $value = parent::getAttributeValue($object, $attribute, $format, $context);

        try {
            $value = $value ?: (\is_callable([$object, '__get']) ? $object->__get($attribute) : null);
        } catch (\Exception $e) {
        }

        return $value;
    }
}
