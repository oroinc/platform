<?php

namespace Oro\Bundle\AddressBundle\ImportExport\Serializer\Normalizer;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface;

class AddressTypeNormalizer implements NormalizerInterface, DenormalizerInterface
{
    const ADDRESS_TYPE_TYPE = 'Oro\Bundle\AddressBundle\Entity\AddressType';

    /**
     * @param AddressType $object
     * @param mixed $format
     * @param array $context
     * @return array
     */
    public function normalize($object, $format = null, array $context = array())
    {
        return $object->getName();
    }

    /**
     * @param mixed $data
     * @param string $class
     * @param mixed $format
     * @param array $context
     * @return AddressType
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        return new AddressType($data);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = array())
    {
        return $data instanceof AddressType;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = array())
    {
        return is_string($data) && $type == self::ADDRESS_TYPE_TYPE;
    }
}
