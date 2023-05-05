<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets "hasMore" flag for the entity and all its associations on all nesting levels.
 */
class SetHasMore implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if (!$definition->isExcludeAll()) {
            // expected completed configs
            return;
        }

        $this->setHasMore($definition);
    }

    private function setHasMore(EntityDefinitionConfig $definition): void
    {
        $definition->setHasMore(true);
        $fields = $definition->getFields();
        foreach ($fields as $field) {
            $targetConfig = $field->getTargetEntity();
            if (null !== $targetConfig) {
                $this->setHasMore($targetConfig);
            }
        }
    }
}
