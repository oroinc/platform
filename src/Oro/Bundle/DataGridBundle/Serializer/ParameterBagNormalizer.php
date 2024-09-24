<?php

namespace Oro\Bundle\DataGridBundle\Serializer;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * Normalizer for DataGrid ParameterBag.
 */
class ParameterBagNormalizer extends AbstractObjectNormalizer
{
    /**
     * @param ParameterBag $object
     *
     */
    #[\Override]
    protected function getAttributeValue($object, $attribute, $format = null, array $context = [])
    {
        return $object->get($attribute);
    }

    /**
     * @param ParameterBag $object
     *
     */
    #[\Override]
    protected function extractAttributes($object, $format = null, array $context = []): array
    {
        return $object->keys();
    }

    /**
     * @param ParameterBag $object
     *
     */
    #[\Override]
    protected function setAttributeValue($object, $attribute, $value, $format = null, array $context = [])
    {
        $object->set($attribute, $value);
    }

    #[\Override]
    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof ParameterBag;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            ParameterBag::class => false
        ];
    }
}
