<?php

namespace Oro\Bundle\ApiBundle\Processor\Create\JsonApi;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\ApiBundle\Request\JsonApi\TypedRequestDataValidator;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Validates that the request data contains valid JSON:API object.
 */
class ValidateRequestData implements ProcessorInterface
{
    public const OPERATION_NAME = 'validate_request_data';

    private ValueNormalizer $valueNormalizer;

    public function __construct(ValueNormalizer $valueNormalizer)
    {
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CreateContext $context */

        if ($context->isProcessed(self::OPERATION_NAME)) {
            // the request data were already validated
            return;
        }

        $errors = $this->validateRequestData($context);
        foreach ($errors as $error) {
            $context->addError($error);
        }
        $context->setProcessed(self::OPERATION_NAME);
    }

    /**
     * @param CreateContext $context
     *
     * @return Error[]
     */
    private function validateRequestData(CreateContext $context): array
    {
        $requestType = $context->getRequestType();
        $validator = new TypedRequestDataValidator(function ($entityType) use ($requestType) {
            return ValueNormalizerUtil::tryConvertToEntityClass($this->valueNormalizer, $entityType, $requestType);
        });

        if ($context->hasIdentifierFields()) {
            return $validator->validateResourceObject(
                $context->getRequestData(),
                true,
                $context->getClassName()
            );
        }

        return $validator->validateMetaObject($context->getRequestData());
    }
}
