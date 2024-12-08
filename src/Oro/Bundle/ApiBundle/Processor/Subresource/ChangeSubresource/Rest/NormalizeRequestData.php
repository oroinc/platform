<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresource\Rest;

use Oro\Bundle\ApiBundle\Processor\Shared\Rest\AbstractNormalizeRequestData;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Prepares REST API request data for a sub-resource to be processed by Symfony Forms.
 */
class NormalizeRequestData extends AbstractNormalizeRequestData
{
    public const OPERATION_NAME = 'normalize_request_data';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ChangeSubresourceContext $context */

        if ($context->isProcessed(self::OPERATION_NAME)) {
            // the request data are already normalized
            return;
        }

        $metadata = $context->getRequestMetadata();
        if (null === $metadata) {
            $context->setProcessed(self::OPERATION_NAME);

            return;
        }

        $data = $context->getRequestData();
        $this->context = $context;
        try {
            if ($context->isCollection()) {
                $normalizedData = [];
                foreach ($data as $key => $value) {
                    if (\is_array($value)) {
                        $this->requestDataItemKey = (string)$key;
                        try {
                            $normalizedData[$key] = $this->normalizeData($value, $metadata);
                        } finally {
                            $this->requestDataItemKey = null;
                        }
                    } else {
                        $normalizedData[$key] = $this->normalizeData($value, $metadata);
                    }
                }
            } else {
                $normalizedData = $this->normalizeData($data, $metadata);
            }
            $context->setRequestData($normalizedData);
        } finally {
            $this->context = null;
        }
        $context->setProcessed(self::OPERATION_NAME);
    }
}
