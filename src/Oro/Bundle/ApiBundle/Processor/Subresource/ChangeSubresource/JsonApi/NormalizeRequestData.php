<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresource\JsonApi;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\AbstractNormalizeRequestData;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Prepares JSON.API request data for a sub-resource to be processed by Symfony Forms.
 */
class NormalizeRequestData extends AbstractNormalizeRequestData
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ChangeSubresourceContext $context */

        $requestData = $context->getRequestData();
        if ($context->hasIdentifierFields()) {
            if (\array_key_exists(JsonApiDoc::DATA, $requestData)) {
                $data = $requestData[JsonApiDoc::DATA];
                if (!\is_array($data)) {
                    // the request data were not validated
                    throw new RuntimeException(
                        \sprintf('The "%s" top-section of the request data should be an array.', JsonApiDoc::DATA)
                    );
                }
                $metadata = $context->getMetadata();
                $this->context = $context;
                try {
                    $pointer = $this->buildPointer(self::ROOT_POINTER, JsonApiDoc::DATA);
                    if ($context->isCollection()) {
                        $normalizedData = [];
                        foreach ($data as $key => $value) {
                            $normalizedData[$key] = $this->normalizeData(
                                $this->buildPointer($pointer, (string)$key),
                                $value,
                                $metadata
                            );
                        }
                    } else {
                        $normalizedData = $this->normalizeData($pointer, $data, $metadata);
                    }
                    $context->setRequestData($normalizedData);
                } finally {
                    $this->context = null;
                }
            }
        } elseif (\array_key_exists(JsonApiDoc::META, $requestData)) {
            $context->setRequestData($requestData[JsonApiDoc::META]);
        }
    }
}
