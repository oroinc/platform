<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Removes all sorters marked as excluded.
 * Updates the property path attribute for existing sorters.
 * Extracts sorters from the definitions of related entities.
 */
class NormalizeSorters extends NormalizeSection
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $this->normalize(
            $context->getSorters(),
            ConfigUtil::SORTERS,
            $context->getClassName(),
            $context->getResult()
        );
    }
}
