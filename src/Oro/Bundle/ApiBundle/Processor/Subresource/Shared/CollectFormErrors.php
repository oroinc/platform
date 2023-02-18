<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\Shared\CollectFormErrors as BaseCollectFormErrors;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Collects errors occurred when submitting forms for primary and included entities
 * and adds them into the context.
 */
class CollectFormErrors extends BaseCollectFormErrors
{
    /**
     * {@inheritdoc}
     */
    protected function collectFormErrors(FormContext $context): void
    {
        parent::collectFormErrors($context);

        // remove the association name from the begin of the property path of error source
        if ($context->hasErrors()) {
            /** @var SubresourceContext $context */
            $associationName = $context->getAssociationName();
            $errors = $context->getErrors();
            foreach ($errors as $error) {
                $errorSource = $error->getSource();
                if (!$errorSource) {
                    continue;
                }
                $propertyPath = $errorSource->getPropertyPath();
                if (!$propertyPath) {
                    continue;
                }

                if ($propertyPath === $associationName) {
                    $errorSource->setPropertyPath('');
                } elseif (str_starts_with($propertyPath, $associationName . ConfigUtil::PATH_DELIMITER)) {
                    $errorSource->setPropertyPath(substr($propertyPath, \strlen($associationName) + 1));
                }
            }
        }
    }
}
