<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\NormalizeResultContext;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterInterface;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * Provides functionality to complete API errors.
 */
trait CompleteErrorsTrait
{
    private function completeErrors(
        array $errors,
        ErrorCompleterInterface $errorCompleter,
        RequestType $requestType,
        ?EntityMetadata $metadata
    ): void {
        foreach ($errors as $error) {
            $errorCompleter->complete($error, $requestType, $metadata);
        }
    }

    private function removeDuplicates(NormalizeResultContext $context): void
    {
        $errors = $context->getErrors();
        if (\count($errors) > 1) {
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

    private function isEntityClass(?string $value): bool
    {
        return $value && str_contains($value, '\\');
    }
}
