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

        $translation = $this->translationManager->createTranslation(
            $data['key'],
            $data['value'],
            $context['language_code'],
            $data['domain']
        );

        $translation->setScope(Translation::SCOPE_UI);

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
