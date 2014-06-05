<?php

namespace Oro\Bundle\AddressBundle\ImportExport\Serializer\Normalizer;

use Oro\Bundle\AddressBundle\Entity\AbstractPhone;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface;

class PhoneNormalizer implements NormalizerInterface, DenormalizerInterface
{
    const ABSTRACT_PHONE_TYPE = 'Oro\Bundle\AddressBundle\Entity\AbstractPhone';

    /**
     * @param AbstractPhone $object
     * @param mixed $format
     * @param array $context
     * @return array
     */
    public function normalize($object, $format = null, array $context = array())
    {
        return $object->getPhone();
    }

    /**
     * @param mixed $data
     * @param string $class
     * @param mixed $format
     * @param array $context
     * @return AbstractPhone
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        /** @var AbstractPhone $result */
        $result = new $class();
        $result->setPhone($data);
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = array())
    {
        return $data instanceof AbstractPhone;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = array())
    {
        return
            is_string($data)
            && class_exists($type)
            && in_array(self::ABSTRACT_PHONE_TYPE, class_parents($type));
    }
}
