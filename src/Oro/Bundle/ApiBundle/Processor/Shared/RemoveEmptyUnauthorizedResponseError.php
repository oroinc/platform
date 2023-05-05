<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Removes an error for 401 (Unauthorized) status code when this error has empty "detail" property.
 */
class RemoveEmptyUnauthorizedResponseError implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $errors = $context->getErrors();
        if (!$errors) {
            return;
        }

        $context->resetErrors();
        foreach ($errors as $error) {
            if ($error->getStatusCode() === Response::HTTP_UNAUTHORIZED && !$error->getDetail()) {
                continue;
            }
            $context->addError($error);
        }
    }
}
