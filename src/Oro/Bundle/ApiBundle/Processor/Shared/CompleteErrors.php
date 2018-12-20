<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterRegistry;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Checks if there are any errors in the context,
 * and if so, completes missing properties of all Error objects.
 * E.g. if an error is created due to an exception occurs,
 * such error does not have "statusCode", "title", "details" and other properties,
 * and these properties are extracted from the Exception object.
 */
class CompleteErrors implements ProcessorInterface
{
    /** @var ErrorCompleterRegistry */
    private $errorCompleterRegistry;

    /**
     * @param ErrorCompleterRegistry $errorCompleterRegistry
     */
    public function __construct(ErrorCompleterRegistry $errorCompleterRegistry)
    {
        $this->errorCompleterRegistry = $errorCompleterRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
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
    }

    /**
     * @param Context $context
     *
     * @return EntityMetadata|null
     */
    private function getMetadata(Context $context)
    {
        $entityClass = $context->getClassName();
        if (!$entityClass || false === strpos($entityClass, '\\')) {
            return null;
        }

        try {
            return $context->getMetadata();
        } catch (\Exception $e) {
            return null;
        }
    }
}
