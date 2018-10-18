<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresource;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Prepares the result data of a change sub-resource request.
 */
class PrepareResultData implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ChangeSubresourceContext $context */

        $associationName = $context->getAssociationName();

        $data = $context->getResult();
        if (!\is_array($data) || !\array_key_exists($associationName, $data) || \count($data) !== 1) {
            // unsupported data
            return;
        }

        $resultData = $data[$associationName];
        if ($resultData instanceof Collection) {
            $resultData = $resultData->toArray();
        }

        $context->setResult($resultData);
    }
}
