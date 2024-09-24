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

    public function __construct(array $actionsWithApplicableCache = [])
    {
        $this->actionsWithApplicableCacheMap = array_fill_keys($actionsWithApplicableCache, true);
    }

    #[\Override]
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

        return new ProcessorIterator(
            $processors,
            $context,
            $applicableChecker,
            $processorRegistry,
            $this->applicableCaches[$action] ?? null
        );
    }

    #[\Override]
    public function reset(): void
    {
        $this->applicableCaches = [];
    }
}
