<?php

namespace Oro\Bundle\TranslationBundle\ImportExport\Serializer;

use Oro\Bundle\ImportExportBundle\Exception\UnexpectedValueException;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Denormalizes the translation data into the Translation object.
 */
class TranslationNormalizer implements DenormalizerInterface
{
    protected TranslationManager $translationManager;

    public function __construct(TranslationManager $translationManager)
    {
        $this->translationManager = $translationManager;
    }

    #[\Override]
    public function denormalize($data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (!is_array($data) || !isset($data['domain'], $data['key'], $data['value'])) {
            throw new UnexpectedValueException('Incorrect record format');
        }

        $translation = $this->translationManager->createTranslation(
            $data['key'],
            $data['value'],
            $context['language_code'],
            $data['domain']
        );

        $translation->setScope(Translation::SCOPE_UI);

        return $translation;
    }

    #[\Override]
    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === Translation::class && !empty($context['language_code']);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Translation::class => true];
    }
}
