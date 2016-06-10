<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Processor\Shared\CollectFormErrors as BaseCollectFormErrors;

/**
 * Collects errors occurred during the the form submit and adds them into the Context.
 */
class CollectFormErrors extends BaseCollectFormErrors
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SubresourceContext $context */

        parent::process($context);

        // remove the association name from the begin of the property path of form errors
        if ($context->hasErrors()) {
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
                } elseif (0 === strpos($propertyPath, $associationName . '.')) {
                    $errorSource->setPropertyPath(substr($propertyPath, strlen($associationName)));
                }
            }
        }
    }
}
