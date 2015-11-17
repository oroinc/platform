<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetFieldConfig;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class LoadFromMetadata implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FieldConfigContext $context */

        $config = $context->getResult();
        if (null !== $config) {
            // a config already exists
            return;
        }

        $entityClass = $context->getClassName();
        if (!$entityClass) {
            // an entity type is not specified
            return;
        }

        $context->setResult(null);
    }
}
