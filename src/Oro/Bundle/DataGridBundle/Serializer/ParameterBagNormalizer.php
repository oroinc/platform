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
     * {@inheritdoc}
     */
    protected function getAttributeValue($object, $attribute, $format = null, array $context = [])
    {
        return $object->get($attribute);
    }

    /**
     * @param ParameterBag $object
     *
     * {@inheritdoc}
     */
    protected function extractAttributes($object, $format = null, array $context = [])
    {
        return $object->keys();
    }

    /**
     * @param ParameterBag $object
     *
     * {@inheritdoc}
     */
    protected function setAttributeValue($object, $attribute, $value, $format = null, array $context = [])
    {
        $object->set($attribute, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof ParameterBag;
    }
}
