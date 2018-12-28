<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets "custom_fields" as default value for "exclusion_policy" option.
 */
class ExcludeCustomFieldsByDefault implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if (!$definition->hasExclusionPolicy()) {
            $definition->setExclusionPolicy(ConfigUtil::EXCLUSION_POLICY_CUSTOM_FIELDS);
        }
    }
}
