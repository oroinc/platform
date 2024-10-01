<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads default data for a computed association.
 */
class LoadDefaultDataForComputedAssociation implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var SubresourceContext $context */

        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        if ($context->hasQuery()) {
            // default data should be loaded only if there is no a query to load data
            return;
        }

        $associationField = $context->getParentConfig()?->getField($context->getAssociationName());
        if (null === $associationField || ConfigUtil::IGNORE_PROPERTY_PATH !== $associationField->getPropertyPath()) {
            // default data should be loaded only for a computed association
            return;
        }

        $context->setResult($context->isCollection() ? [] : null);
        // data normalization is not required
        $context->skipGroup(ApiActionGroup::NORMALIZE_DATA);
    }
}
