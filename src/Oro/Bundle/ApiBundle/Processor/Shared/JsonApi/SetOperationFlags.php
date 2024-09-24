<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Util\MetaOperationParser;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Extracts operation flags from the request data and sets them to the context.
 */
class SetOperationFlags implements ProcessorInterface
{
    public const UPDATE_FLAG = '_meta_update';
    public const UPSERT_FLAG = '_meta_upsert';

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var FormContext $context */

        $requestData = $context->getRequestData();
        if (\array_key_exists(JsonApiDoc::DATA, $requestData)) {
            $data = $requestData[JsonApiDoc::DATA];
            if (\is_array($data) && !empty($data[JsonApiDoc::META]) && \is_array($data[JsonApiDoc::META])) {
                $operationFlags = MetaOperationParser::getOperationFlags(
                    $data[JsonApiDoc::META],
                    JsonApiDoc::META_UPDATE,
                    JsonApiDoc::META_UPSERT,
                    '/' . JsonApiDoc::META,
                    $context
                );
                if (null !== $operationFlags) {
                    [$updateFlag, $upsertFlag] = $operationFlags;
                    if (null !== $updateFlag) {
                        $context->set(self::UPDATE_FLAG, $updateFlag);
                    }
                    if (null !== $upsertFlag) {
                        $context->set(self::UPSERT_FLAG, $upsertFlag);
                    }
                }
            }
        }
    }
}
