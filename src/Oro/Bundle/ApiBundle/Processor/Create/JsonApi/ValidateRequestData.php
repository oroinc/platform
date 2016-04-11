<?php

namespace Oro\Bundle\ApiBundle\Processor\Create\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\ValidateRequestData as ParentValidateRequestData;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

class ValidateRequestData extends ParentValidateRequestData
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */

        $requestData = $context->getRequestData();
        $pointer = [JsonApiDoc::DATA];
        if (!$this->validateDataObject($requestData, $pointer, $context)) {
            // we have no data in main object
            return;
        }

        $data = $requestData[JsonApiDoc::DATA];

        $typeExists = true;
        if (!array_key_exists(JsonApiDoc::TYPE, $data)) {
            $this->addError(
                $context,
                array_merge($pointer, [JsonApiDoc::TYPE]),
                sprintf('The \'%s\' parameter is required', JsonApiDoc::TYPE)
            );

            $typeExists = false;
        }

        if ($typeExists) {
            $dataClassName = ValueNormalizerUtil::convertToEntityClass(
                $this->valueNormalizer,
                $data[JsonApiDoc::TYPE],
                $context->getRequestType(),
                false
            );
            if ($dataClassName !== $context->getClassName()) {
                $this->addError(
                    $context,
                    array_merge($pointer, [JsonApiDoc::TYPE]),
                    sprintf(
                        'The \'%s\' parameters in request data and query sting should match each other',
                        JsonApiDoc::TYPE
                    )
                );

            }
        }

        if (!array_key_exists(JsonApiDoc::ATTRIBUTES, $data) && !array_key_exists(JsonApiDoc::RELATIONSHIPS, $data)) {
            $this->addError(
                $context,
                $pointer,
                sprintf(
                    'The primary data object should contain \'%s\' or \'%s\' block',
                    JsonApiDoc::ATTRIBUTES,
                    JsonApiDoc::RELATIONSHIPS
                )
            );
        }

        if (array_key_exists(JsonApiDoc::ATTRIBUTES, $data)) {
            $this->validateAttributes(
                $data[JsonApiDoc::ATTRIBUTES],
                array_merge($pointer, [JsonApiDoc::ATTRIBUTES]),
                $context
            );
        }

        if (array_key_exists(JsonApiDoc::RELATIONSHIPS, $data)) {
            $this->validateRelations(
                $data[JsonApiDoc::RELATIONSHIPS],
                array_merge($pointer, [JsonApiDoc::RELATIONSHIPS]),
                $context
            );
        }
    }
}
