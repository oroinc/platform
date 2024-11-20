<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresource\JsonApi;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Oro\Bundle\ApiBundle\Request\JsonApi\RequestDataValidator;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Validates that the request data contains valid JSON:API object
 * that can be used to update a sub-resource.
 */
class ValidateRequestData implements ProcessorInterface
{
    public const OPERATION_NAME = 'validate_request_data';

    private bool $requirePrimaryResourceId;
    private bool $allowIncludedResources;

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
    public function process(ContextInterface $context): void
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
        $metadata = $context->getRequestMetadata();
        if (null === $metadata) {
            return [];
        }

        if (!$metadata->hasIdentifierFields()) {
            return $this->getValidator()->validateMetaObject($context->getRequestData());
        }

        if ($context->isCollection()) {
            return $this->getValidator()->validateResourceObjectCollection(
                $context->getRequestData(),
                $this->allowIncludedResources,
                $this->requirePrimaryResourceId
            );
        }

        return $this->getValidator()->validateResourceObject(
            $context->getRequestData(),
            $this->allowIncludedResources,
            $this->requirePrimaryResourceId
        );
    }

    private function getValidator(): RequestDataValidator
    {
        return new RequestDataValidator();
    }
}
