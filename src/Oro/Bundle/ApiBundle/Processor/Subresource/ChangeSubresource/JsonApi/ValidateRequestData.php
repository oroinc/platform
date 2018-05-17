<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresource\JsonApi;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Oro\Bundle\ApiBundle\Request\JsonApi\RequestDataValidator;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Validates that the request data contains valid JSON.API object
 * that can be used to update a sub-resource.
 */
class ValidateRequestData implements ProcessorInterface
{
    public const OPERATION_NAME = 'validate_request_data';

    /** @var bool */
    private $requirePrimaryResourceId;

    /** @var bool */
    private $allowIncludedResources;

    /**
     * @param bool $requirePrimaryResourceId
     * @param bool $allowIncludedResources
     */
    public function __construct(
        bool $requirePrimaryResourceId = false,
        bool $allowIncludedResources = false
    ) {
        $this->requirePrimaryResourceId = $requirePrimaryResourceId;
        $this->allowIncludedResources = $allowIncludedResources;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ChangeSubresourceContext $context */

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
     * @param ChangeSubresourceContext $context
     *
     * @return Error[]
     */
    protected function validateRequestData(ChangeSubresourceContext $context): array
    {
        $validator = new RequestDataValidator();

        if (!$context->hasIdentifierFields()) {
            return $validator->validateMetaObject($context->getRequestData());
        }

        if ($context->isCollection()) {
            return $validator->validateResourceObjectCollection(
                $context->getRequestData(),
                $this->allowIncludedResources,
                $this->requirePrimaryResourceId
            );
        }

        return $validator->validateResourceObject(
            $context->getRequestData(),
            $this->allowIncludedResources,
            $this->requirePrimaryResourceId
        );
    }
}
