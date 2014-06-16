<?php

namespace Oro\Bundle\ImportExportBundle\Serializer;

use Doctrine\Common\Collections\Collection;

use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Serializer as BaseSerializer;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface;

class Serializer extends BaseSerializer implements DenormalizerInterface, NormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function normalize($data, $format = null, array $context = array())
    {
        if ($data instanceof Collection) {
            // Clear cache of normalizer for collections,
            // because of wrong behaviour when selecting normalizer for collections of elements with different types
            unset($this->normalizerCache[get_class($data)][$format]);
        }

        return parent::normalize($data, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = array())
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
    public function supportsDenormalization($data, $type, $format = null, array $context = array())
    {
        try {
            $this->getDenormalizer($data, $type, $format, $context);
        } catch (RuntimeException $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    private function getNormalizer($data, $format = null, array $context = array())
    {
        foreach ($this->normalizers as $normalizer) {
            if (!$normalizer instanceof NormalizerInterface) {
                continue;
            }

            /** @var NormalizerInterface $normalizer */
            $supportsNormalization = $normalizer->supportsNormalization($data, $format, $context);

            if ($supportsNormalization) {
                return $normalizer;
            }
        }

        throw new RuntimeException(sprintf('No normalizer found for format "%s".', $format));
    }

    /**
     * {@inheritdoc}
     */
    private function getDenormalizer($data, $type, $format = null, array $context = array())
    {
        foreach ($this->normalizers as $normalizer) {
            if (!$normalizer instanceof DenormalizerInterface) {
                continue;
            }

            /** @var DenormalizerInterface $normalizer */
            $supportsDenormalization = $normalizer->supportsDenormalization(
                $data,
                $type,
                $format,
                $context
            );

            if ($supportsDenormalization) {
                return $normalizer;
            }
        }

        throw new RuntimeException(sprintf('No denormalizer found for format "%s".', $format));
    }
}
