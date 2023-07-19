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
    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();
        $idFieldName = $context->getResultFieldName('id');
        $data[$idFieldName] = TranslationIdUtil::buildTranslationId($data[$idFieldName], $data['languageCode']);
        $context->setData($data);
    }
}
