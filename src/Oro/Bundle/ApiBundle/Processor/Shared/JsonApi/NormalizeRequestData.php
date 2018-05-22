<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Prepares JSON.API request data to be processed by Symfony Forms.
 */
class NormalizeRequestData extends AbstractNormalizeRequestData
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext|SingleItemContext $context */

        $requestData = $context->getRequestData();
        if ($context->hasIdentifierFields()) {
            if (\array_key_exists(JsonApiDoc::DATA, $requestData)) {
                $this->context = $context;
                try {
                    $context->setRequestData(
                        $this->normalizeData(
                            $this->buildPointer(self::ROOT_POINTER, JsonApiDoc::DATA),
                            $requestData[JsonApiDoc::DATA],
                            $context->getMetadata()
                        )
                    );
                } finally {
                    $this->context = null;
                }
            }
        } elseif (\array_key_exists(JsonApiDoc::META, $requestData)) {
            $context->setRequestData($requestData[JsonApiDoc::META]);
        }
    }
}
