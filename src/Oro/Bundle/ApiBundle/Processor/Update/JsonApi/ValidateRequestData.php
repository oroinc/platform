<?php

namespace Oro\Bundle\ApiBundle\Processor\Update\JsonApi;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Update\UpdateContext;
use Oro\Bundle\ApiBundle\Request\JsonApi\TypedRequestDataValidator;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Validates that the request data contains valid JSON.API object.
 */
class ValidateRequestData implements ProcessorInterface
{
    public const OPERATION_NAME = 'validate_request_data';

    /** @var ValueNormalizer */
    private $valueNormalizer;

    /**
     * @param ValueNormalizer $valueNormalizer
     */
    public function __construct(ValueNormalizer $valueNormalizer)
    {
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var UpdateContext $context */

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
     * @param UpdateContext $context
     *
     * @return Error[]
     */
    private function validateRequestData(UpdateContext $context): array
    {
        $requestType = $context->getRequestType();
        $validator = new TypedRequestDataValidator(function ($entityType) use ($requestType) {
            return ValueNormalizerUtil::convertToEntityClass(
                $this->valueNormalizer,
                $entityType,
                $requestType,
                false
            );
        });

        if ($context->hasIdentifierFields()) {
            return $validator->validateResourceObject(
                $context->getRequestData(),
                true,
                $context->getClassName(),
                $context->getId()
            );
        }

        return $validator->validateMetaObject($context->getRequestData());
    }
}
