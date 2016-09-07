<?php

namespace Oro\Bundle\TranslationBundle\ImportExport\Serializer;

use Oro\Bundle\ImportExportBundle\Exception\UnexpectedValueException;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface;

use Oro\Bundle\TranslationBundle\Entity\Translation;

class TranslationNormalizer implements DenormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (!is_array($data) || !isset($data['domain'], $data['key'], $data['value'])) {
            throw new UnexpectedValueException('Incorrect record format');
        }

        $translation = new Translation();
        $translation->setLocale($context['language_code'])
            ->setDomain($data['domain'])
            ->setKey($data['key'])
            ->setValue($data['value']);

        return $translation;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return $type === Translation::class && !empty($context['language_code']);
    }
}
