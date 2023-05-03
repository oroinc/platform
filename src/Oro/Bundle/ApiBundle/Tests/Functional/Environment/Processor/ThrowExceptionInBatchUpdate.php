<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Oro\Bundle\ApiBundle\Batch\Processor\Update\BatchUpdateContext;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Emulates an exception thrown by processors for "batch_update" action.
 */
class ThrowExceptionInBatchUpdate implements ProcessorInterface
{
    private BatchUpdateExceptionController $exceptionController;
    private ?string $stage;

    public function __construct(BatchUpdateExceptionController $exceptionController, string $stage = null)
    {
        $this->exceptionController = $exceptionController;
        $this->stage = $stage;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function process(ContextInterface $context): void
    {
        /** @var BatchUpdateContext $context */

        if ($this->exceptionController->getFailedGroups()) {
            if (null !== $this->stage) {
                foreach ($this->exceptionController->getFailedGroups() as $failedGroup) {
                    if (!str_contains($failedGroup, ':')) {
                        continue;
                    }
                    [$group, $stage] = explode(':', $failedGroup);
                    if ($this->stage === $stage && $context->getFirstGroup() === $group) {
                        throw new RuntimeException(sprintf(
                            'A test exception from the "%s" stage of the "%s" group.',
                            $stage,
                            $context->getFirstGroup()
                        ));
                    }
                }
            } elseif (\in_array($context->getFirstGroup(), $this->exceptionController->getFailedGroups(), true)
                && ($context->getFirstGroup() !== ApiActionGroup::NORMALIZE_RESULT || $context->getSourceGroup())
            ) {
                throw new RuntimeException(sprintf(
                    'A test exception from the "%s" group.',
                    $context->getFirstGroup()
                ));
            }
        }
    }
}
