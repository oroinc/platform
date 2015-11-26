<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\NormalizeDescription;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class NormalizeEntityDescription extends NormalizeDescription
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if (empty($definition)) {
            // an entity configuration does not exist
            return;
        }

        $this->normalizeAttribute($definition, ConfigUtil::LABEL);
        $this->normalizeAttribute($definition, ConfigUtil::PLURAL_LABEL);
        $this->normalizeAttribute($definition, ConfigUtil::DESCRIPTION);

        $context->setResult($definition);
    }
}
