<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterRegistry;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Checks if there are any errors in the context,
 * and if so, completes missing properties of all Error objects.
 * E.g. if an error is created due to an exception occurs,
 * such error does not have "statusCode", "title", "detail" and other properties,
 * and these properties are extracted from the Exception object.
 * Also, removes duplicated errors if any.
 */
class CompleteErrors implements ProcessorInterface
{
    private ErrorCompleterRegistry $errorCompleterRegistry;

    public function __construct(ErrorCompleterRegistry $errorCompleterRegistry)
    {
        $this->errorCompleterRegistry = $errorCompleterRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if (!$context->hasErrors()) {
            // no errors
            return;
        }

        $requestType = $context->getRequestType();
        $errorCompleter = $this->errorCompleterRegistry->getErrorCompleter($requestType);
        $metadata = $this->getMetadata($context);
        $errors = $context->getErrors();
        foreach ($errors as $error) {
            $errorCompleter->complete($error, $requestType, $metadata);
        }
        if (\count($errors) > 1) {
            $this->removeDuplicates($errors, $context);
        }
    }

    /**
     * @param Error[] $errors
     * @param Context $context
     */
    private function removeDuplicates(array $errors, Context $context): void
    {
        $context->resetErrors();
        $map = [];
        foreach ($errors as $error) {
            $key = $this->getErrorHash($error);
            if (!isset($map[$key])) {
                $map[$key] = true;
                $context->addError($error);
            }
        }
    }

    private function getErrorHash(Error $error): string
    {
        $result = serialize([
            $error->getStatusCode(),
            $error->getCode(),
            $error->getTitle(),
            $error->getDetail()
        ]);
        $source = $error->getSource();
        if (null !== $source) {
            $result .= serialize([
                $source->getPropertyPath(),
                $source->getPointer(),
                $source->getParameter()
            ]);
        }

        return $result;
    }

    private function getMetadata(Context $context): ?EntityMetadata
    {
        $entityClass = $context->getClassName();
        if (!$entityClass || !str_contains($entityClass, '\\')) {
            return null;
        }

        try {
            return $context->getMetadata();
        } catch (\Exception $e) {
            return null;
        }
    }
}
