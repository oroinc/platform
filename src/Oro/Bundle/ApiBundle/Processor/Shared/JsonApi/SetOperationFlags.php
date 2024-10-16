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
    public const string UPDATE_FLAG = '_meta_update';
    public const string UPSERT_FLAG = '_meta_upsert';
    public const string VALIDATE_FLAG = '_meta_validate';

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
                    JsonApiDoc::META_VALIDATE,
                    '/' . JsonApiDoc::META,
                    $context
                );
                if (null !== $operationFlags) {
                    [$updateFlag, $upsertFlag, $validateFlag] = $operationFlags;
                    if (null !== $updateFlag) {
                        $context->set(self::UPDATE_FLAG, $updateFlag);
                    }
                    if (null !== $upsertFlag) {
                        $context->set(self::UPSERT_FLAG, $upsertFlag);
                    }
                    if (null !== $validateFlag) {
                        $context->set(self::VALIDATE_FLAG, $validateFlag);
                    }
                }
            }
        }
    }
}
