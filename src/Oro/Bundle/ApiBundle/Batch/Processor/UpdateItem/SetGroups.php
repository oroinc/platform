<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * A base processor for setting the first and the last group to the context of target action.
 */
abstract class SetGroups implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var BatchUpdateItemContext $context */

        $targetAction = $context->getTargetAction();
        if (!$targetAction) {
            throw new RuntimeException('The target action is not defined.');
        }
        $targetContext = $context->getTargetContext();
        if (null === $targetContext) {
            throw new RuntimeException('The target context is not defined.');
        }

        $this->setGroups($targetContext, $targetAction);
    }

    abstract protected function setGroups(Context $targetContext, string $targetAction): void;
}
