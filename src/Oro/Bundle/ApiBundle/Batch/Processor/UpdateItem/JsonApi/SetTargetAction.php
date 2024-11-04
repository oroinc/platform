<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\JsonApi;

use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\BatchUpdateItemContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Util\MetaOperationParser;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Determines the target action based on the request data and adds it to the context.
 */
class SetTargetAction implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var BatchUpdateItemContext $context */

        $requestData = $context->getRequestData();
        if (\is_array($requestData) && \array_key_exists(JsonApiDoc::DATA, $requestData)) {
            $data = $requestData[JsonApiDoc::DATA];
            $context->setTargetAction(
                \is_array($data) && !empty($data[JsonApiDoc::META]) && \is_array($data[JsonApiDoc::META])
                    ? $this->getTargetAction($data[JsonApiDoc::META])
                    : ApiAction::CREATE
            );
        }
    }

    private function getTargetAction(array $meta): string
    {
        $operationFlags = MetaOperationParser::getOperationFlags(
            $meta,
            JsonApiDoc::META_UPDATE,
            JsonApiDoc::META_UPSERT,
            JsonApiDoc::META_VALIDATE
        );

        if (null === $operationFlags) {
            return ApiAction::CREATE;
        }

        return $operationFlags[0] || true === $operationFlags[1]
            ? ApiAction::UPDATE
            : ApiAction::CREATE;
    }
}
