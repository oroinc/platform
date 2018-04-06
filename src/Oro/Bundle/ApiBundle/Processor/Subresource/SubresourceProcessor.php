<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource;

use Oro\Bundle\ApiBundle\Processor\NormalizeResultContext;
use Oro\Bundle\ApiBundle\Processor\RequestActionProcessor;

/**
 * The base class for subresource related action processors.
 */
class SubresourceProcessor extends RequestActionProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function getLogContext(NormalizeResultContext $context): array
    {
        /** @var SubresourceContext $context */

        $result = parent::getLogContext($context);
        $associationClass = $result['class'];
        unset($result['class']);
        $result['parentClass'] = $context->getParentClassName();
        $result['parentId'] = $context->getParentId();
        $result['association'] = $context->getAssociationName();
        $result['associationClass'] = $associationClass;

        return $result;
    }
}
