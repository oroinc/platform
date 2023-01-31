<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\JsonApi;

use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\BatchUpdateItemContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Determines the target action based on the request data and adds it to the context.
 */
class SetTargetAction implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var BatchUpdateItemContext $context */

        $requestData = $context->getRequestData();
        if (\is_array($requestData) && \array_key_exists(JsonApiDoc::DATA, $requestData)) {
            $action = ApiAction::CREATE;
            $data = $requestData[JsonApiDoc::DATA];
            if (\is_array($data) && !empty($data[JsonApiDoc::META])) {
                $meta = $data[JsonApiDoc::META];
                if (\is_array($meta) && true === ($meta[JsonApiDoc::META_UPDATE] ?? null)) {
                    $action = ApiAction::UPDATE;
                }
            }
            $context->setTargetAction($action);
        }
    }
}
