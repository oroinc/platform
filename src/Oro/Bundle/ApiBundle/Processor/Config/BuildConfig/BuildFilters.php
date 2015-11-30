<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\BuildConfig;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class BuildFilters implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        if ($context->hasFilters()) {
            // a filters' definition is already built
            return;
        }

        $context->setFilters(ConfigUtil::getInitialConfig());
    }
}
