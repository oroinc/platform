<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresource\Rest;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\Shared\Rest\AbstractNormalizeRequestData;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Prepares REST API request data for a sub-resource to be processed by Symfony Forms.
 */
class NormalizeRequestData extends AbstractNormalizeRequestData
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ChangeSubresourceContext $context */

        $data = $context->getRequestData();
        if (!\is_array($data)) {
            // the request data were not validated
            throw new RuntimeException('The request data should be an array.');
        }

        $metadata = $context->getMetadata();
        $this->context = $context;
        try {
            if ($context->isCollection()) {
                $normalizedData = [];
                foreach ($data as $key => $value) {
                    $normalizedData[$key] = $this->normalizeData($data, $metadata);
                }
            } else {
                $normalizedData = $this->normalizeData($data, $metadata);
            }
            $context->setRequestData($normalizedData);
        } finally {
            $this->context = null;
        }
    }
}
