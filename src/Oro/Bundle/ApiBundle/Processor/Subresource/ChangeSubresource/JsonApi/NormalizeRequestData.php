<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresource\JsonApi;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\AbstractNormalizeRequestData;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Prepares JSON:API request data for a sub-resource to be processed by Symfony Forms.
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

        $requestData = $context->getRequestData();
        if ($context->getRequestMetadata()?->hasIdentifierFields()) {
            if (\array_key_exists(JsonApiDoc::DATA, $requestData)) {
                $data = $requestData[JsonApiDoc::DATA];
                if (!\is_array($data)) {
                    // the request data were not validated
                    throw new RuntimeException(\sprintf(
                        'The "%s" top-section of the request data should be an array.',
                        JsonApiDoc::DATA
                    ));
                }
                $metadata = $context->getRequestMetadata();
                $this->context = $context;
                try {
                    $path = '';
                    $pointer = $this->buildPointer(self::ROOT_POINTER, JsonApiDoc::DATA);
                    if ($context->isCollection()) {
                        $normalizedData = [];
                        foreach ($data as $key => $value) {
                            $normalizedData[$key] = $this->normalizeData(
                                $this->buildPath($path, (string)$key),
                                $this->buildPointer($pointer, (string)$key),
                                $value,
                                $metadata
                            );
                        }
                    } else {
                        $normalizedData = $this->normalizeData($path, $pointer, $data, $metadata);
                    }
                    $context->setRequestData($normalizedData);
                } finally {
                    $this->context = null;
                }
            }
        } elseif (\array_key_exists(JsonApiDoc::META, $requestData)) {
            $context->setRequestData($requestData[JsonApiDoc::META]);
        }
        $context->setProcessed(self::OPERATION_NAME);
    }
}
