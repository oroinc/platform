<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "email" field for EmailAttachment entity.
 */
class ComputeEmailAttachmentEmail implements ProcessorInterface
{
    private const EMAIL_FIELD_NAME = 'email';

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();
        $value = reset($data[self::EMAIL_FIELD_NAME]);
        $data[self::EMAIL_FIELD_NAME] = false !== $value ? $value : null;
        $context->setData($data);
    }
}
