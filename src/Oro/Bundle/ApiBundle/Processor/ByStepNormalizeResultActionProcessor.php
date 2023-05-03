<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Component\ChainProcessor\ContextInterface as ComponentContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * The base processor for actions with "normalize_result" group
 * and that execute processors only from one group at the same time.
 */
class ByStepNormalizeResultActionProcessor extends NormalizeResultActionProcessor
{
    /**
     * {@inheritDoc}
     */
    public function process(ComponentContextInterface $context): void
    {
        /** @var ByStepNormalizeResultContext $context */

        if (!$context->getFirstGroup() || $context->getFirstGroup() !== $context->getLastGroup()) {
            throw new \LogicException(sprintf(
                'Both the first and the last groups must be specified for the "%s" action'
                . ' and these groups must be equal. First Group: "%s". Last Group: "%s".',
                $this->getAction(),
                $context->getFirstGroup(),
                $context->getLastGroup()
            ));
        }
        $context->resetSkippedGroups();
        $context->setSourceGroup(null);
        $context->setFailedGroup(null);

        parent::process($context);
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function executeProcessors(ComponentContextInterface $context): void
    {
        /** @var ByStepNormalizeResultContext $context */

        $sourceGroup = $context->getFirstGroup();
        if ($context->hasErrors()) {
            $initialErrorCount = \count($context->getErrors());
            $processors = $this->processorBag->getProcessors($context);
            $processorId = null;
            $group = null;
            try {
                $errorsHandled = false;
                /** @var ProcessorInterface $processor */
                foreach ($processors as $processor) {
                    if (\count($context->getErrors()) > $initialErrorCount) {
                        $errorsHandled = true;
                        if (ApiActionGroup::NORMALIZE_RESULT !== $group) {
                            $this->handleErrors($context, $processorId, $group);
                            break;
                        }
                    }
                    $processorId = $processors->getProcessorId();
                    $group = $processors->getGroup();
                    $processor->process($context);
                }
                if (!$errorsHandled && \count($context->getErrors()) > $initialErrorCount) {
                    $this->handleErrors($context, $processorId, $group);
                }
            } catch (\Error $e) {
                $this->handlePhpError($e, $context, $processorId, $group);
            } catch (\Exception $e) {
                $this->handleException($e, $context, $processorId, $group);
            }
        } else {
            parent::executeProcessors($context);
        }
        if ($context->getFirstGroup() !== ApiActionGroup::NORMALIZE_RESULT) {
            $context->setSourceGroup($sourceGroup);
            $context->setFailedGroup('');
            $this->executeNormalizeResultProcessors($context);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function handleErrors(NormalizeResultContext $context, string $processorId, ?string $group): void
    {
        /** @var ByStepNormalizeResultContext $context */

        if (ApiActionGroup::NORMALIZE_RESULT !== $group) {
            $context->setSourceGroup('');
            $context->setFailedGroup($group);
        }
        parent::handleErrors($context, $processorId, $group);
    }

    /**
     * {@inheritDoc}
     */
    protected function handleException(
        \Exception $e,
        NormalizeResultContext $context,
        string $processorId,
        ?string $group
    ): void {
        /** @var ByStepNormalizeResultContext $context */

        if (ApiActionGroup::NORMALIZE_RESULT !== $group) {
            $context->setSourceGroup('');
            $context->setFailedGroup($group);
        }
        parent::handleException($e, $context, $processorId, $group);
    }

    /**
     * {@inheritDoc}
     */
    protected function isNormalizeResultEnabled(NormalizeResultContext $context): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function executeNormalizeResultProcessors(NormalizeResultContext $context): void
    {
        $context->setLastGroup(ApiActionGroup::NORMALIZE_RESULT);
        parent::executeNormalizeResultProcessors($context);
    }
}
