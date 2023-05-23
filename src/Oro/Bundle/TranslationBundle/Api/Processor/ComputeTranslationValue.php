<?php

namespace Oro\Bundle\TranslationBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Computes values of the following fields for Translation entity:
 * * value
 * * englishValue
 */
class ComputeTranslationValue implements ProcessorInterface
{
    private const VALUE_FIELD_NAME = 'value';
    private const ENGLISH_VALUE_FIELD_NAME = 'englishValue';

    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        $isValueRequested = $context->isFieldRequested(self::VALUE_FIELD_NAME, $data);
        $isEnglishValueRequested = $context->isFieldRequested(self::ENGLISH_VALUE_FIELD_NAME, $data);
        if ($isValueRequested || $isEnglishValueRequested) {
            $key = $data[$context->getResultFieldName('key')];
            $domain = $data[$context->getResultFieldName('domain')];
            if ($isValueRequested) {
                $data[self::VALUE_FIELD_NAME] = $this->translator->trans(
                    $key,
                    [],
                    $domain,
                    $data['languageCode']
                );
            }
            if ($isEnglishValueRequested) {
                $data[self::ENGLISH_VALUE_FIELD_NAME] = $this->translator->trans(
                    $key,
                    [],
                    $domain,
                    Translator::DEFAULT_LOCALE
                );
            }
        }

        $context->setData($data);
    }
}
