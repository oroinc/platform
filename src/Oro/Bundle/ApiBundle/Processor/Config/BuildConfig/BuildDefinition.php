<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\BuildConfig;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class BuildDefinition implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        if ($context->hasResult()) {
            // a definition is already built
            return;
        }

        $context->setResult(ConfigUtil::getInitialConfig());
    }
}
