<?php

namespace Oro\Bundle\TranslationBundle\ImportExport\Serializer;

use Oro\Bundle\ImportExportBundle\Exception\UnexpectedValueException;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;

class TranslationNormalizer implements DenormalizerInterface
{
    /** @var TranslationManager */
    protected $translationManager;

    /**
     * @param TranslationManager $translationManager
     */
    public function __construct(TranslationManager $translationManager)
    {
        $this->translationManager = $translationManager;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (!is_array($data) || !isset($data['domain'], $data['key'], $data['value'])) {
            throw new UnexpectedValueException('Incorrect record format');
        }

        $language = $this->translationManager->getLanguageByCode($context['language_code']);
        $translationKey = $this->translationManager->findTranslationKey($data['key'], $data['domain']);

        $translation = new Translation();
        $translation->setLanguage($language)
            ->setTranslationKey($translationKey)
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
