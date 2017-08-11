<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Component\ChainProcessor\ContextInterface;

trait ContextErrorUtilTrait
{
    /**
     * @param string $pointer
     * @param string $message
     * @param ContextInterface $context
     * @param string|null $title
     */
    protected function addError($pointer, $message, ContextInterface $context, string $title = null)
    {
        $error = Error::createValidationError(Constraint::REQUEST_DATA, $message)
            ->setSource(ErrorSource::createByPointer($pointer));
        if (null !== $title) {
            $error->setTitle($title);
        }

        $context->addError($error);
    }

    /**
     * @param array $properties
     * @param string|null $parentPointer
     * @return string
     *
     */
    protected function buildPointer(array $properties, $parentPointer = null)
    {
        array_unshift($properties, $parentPointer);

        return implode('/', $properties);
    }
}
