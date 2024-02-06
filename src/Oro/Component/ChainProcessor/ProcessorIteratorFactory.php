<?php

namespace Oro\Component\ChainProcessor;

use Symfony\Contracts\Service\ResetInterface;

/**
 * The factory to create an instance of ProcessorIterator class.
 */
class ProcessorIteratorFactory implements ProcessorIteratorFactoryInterface, ResetInterface
{
    private array $actionsWithApplicableCacheMap;
    /** @var ParameterBagInterface[] [action => cache object, ...] */
    private array $applicableCaches = [];

    public function setActionsWithApplicableCache(array $actionsWithApplicableCache): void
    {
        $this->actionsWithApplicableCacheMap = array_fill_keys($actionsWithApplicableCache, true);
    }

    /**
     * {@inheritDoc}
     */
    public function createProcessorIterator(
        array $processors,
        ContextInterface $context,
        ApplicableCheckerInterface $applicableChecker,
        ProcessorRegistryInterface $processorRegistry
    ): ProcessorIterator {
        $action = $context->getAction();

        if (isset($this->actionsWithApplicableCacheMap[$action]) && !isset($this->applicableCaches[$action])) {
            $this->applicableCaches[$action] = new ParameterBag();
        }

        $iterator = new ProcessorIterator(
            $processors,
            $context,
            $applicableChecker,
            $processorRegistry
        );
        if (isset($this->applicableCaches[$action])) {
            $iterator->setApplicableCache($this->applicableCaches[$action]);
        }

        return $iterator;
    }

    /**
     * {@inheritDoc}
     */
    public function reset(): void
    {
        $this->applicableCaches = [];
    }
}
