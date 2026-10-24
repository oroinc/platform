<?php

namespace Oro\Bundle\TranslationBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\TranslationBundle\Api\TranslationIdUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets ID in the 'translationKeyId-languageCode' format to the Translation entity response data.
 */
class ComputeTranslationId implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();
        $idFieldName = $context->getResultFieldName('id');

        $id = $data[$idFieldName];
        if (\is_string($id) && null !== TranslationIdUtil::extractTranslationKeyId($id)) {
            return;
        }

        $languageCode = $data[$context->getResultFieldName('languageCode')];
        if (\is_string($id) && TranslationIdUtil::extractLanguageCode($id) === $languageCode) {
            return;
        }

        $data[$idFieldName] = TranslationIdUtil::buildTranslationId($id, $languageCode);
        $context->setData($data);
    }
}
