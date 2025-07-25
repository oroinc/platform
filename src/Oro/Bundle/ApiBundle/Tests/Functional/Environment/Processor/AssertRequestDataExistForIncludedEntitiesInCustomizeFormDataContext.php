<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class AssertRequestDataExistForIncludedEntitiesInCustomizeFormDataContext implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $includedEntities = $context->getIncludedEntities();
        if (null === $includedEntities) {
            return;
        }

        if (null === $includedEntities->getPrimaryEntityRequestData()) {
            throw new RuntimeException(\sprintf(
                'Request data is not set for the primary entity. Action: %s. Event: %s. Class: %s.',
                $context->getAction(),
                $context->getEvent(),
                $context->getClassName()
            ));
        }
        foreach ($includedEntities as $includedEntity) {
            $includedEntityData = $includedEntities->getData($includedEntity);
            if (null === $includedEntityData->getRequestData()) {
                throw new RuntimeException(\sprintf(
                    'Request data is not set for an included entity. Action: %s. Event: %s.'
                    . ' Class: %s. Included Entity Index: %d.',
                    $context->getAction(),
                    $context->getEvent(),
                    $context->getClassName(),
                    $includedEntityData->getIndex()
                ));
            }
        }
    }
}
