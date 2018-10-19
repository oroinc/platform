<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
use Oro\Bundle\ApiBundle\Request\JsonApi\RelationshipRequestDataValidator;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Validates that the request data contains valid JSON.API object
 * that can be used to update a relationship.
 */
class ValidateRequestData implements ProcessorInterface
{
    public const OPERATION_NAME = 'validate_request_data';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ChangeRelationshipContext $context */

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
     * @param ChangeRelationshipContext $context
     *
     * @return Error[]
     */
    private function validateRequestData(ChangeRelationshipContext $context): array
    {
        $validator = new RelationshipRequestDataValidator();

        if ($context->isCollection()) {
            return $validator->validateResourceIdentifierObjectCollection($context->getRequestData());
        }

        return $validator->validateResourceIdentifierObject($context->getRequestData());
    }
}
