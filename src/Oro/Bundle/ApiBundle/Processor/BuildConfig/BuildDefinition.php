<?php

namespace Oro\Bundle\ApiBundle\Processor\BuildConfig;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\ConfigContext;

class BuildDefinition implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        if ($context->hasResult()) {
            // a definition is already built
            return;
        }

        $entityClass = $context->getClassName();
        if (!$entityClass) {
            // an entity type is not specified
            return;
        }

        $context->setResult(
            [
                'exclusion_policy' => 'none',
                'fields'           => []
            ]
        );
    }
}
