<?php

namespace Oro\Bundle\ImportExportBundle\Serializer;

use Doctrine\Common\Collections\Collection;

use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Serializer as BaseSerializer;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Exception\LogicException;

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
    public function denormalize($data, $type, $format = null, array $context = array())
    {
        if (!$this->normalizers) {
            throw new LogicException('You must register at least one normalizer to be able to denormalize objects.');
        }

        if (isset($this->denormalizerCache[$type][$format])) {
            return $this->denormalizerCache[$type][$format]->denormalize($data, $type, $format, $context);
        }

        foreach ($this->normalizers as $normalizer) {
            if ($normalizer instanceof DenormalizerInterface
                && $normalizer->supportsDenormalization($data, $type, $format, $context)) {
                $this->denormalizerCache[$type][$format] = $normalizer;

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
