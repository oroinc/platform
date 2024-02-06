<?php

namespace Oro\Component\ChainProcessor;

/**
 * Iterates over processors filtered by matching theirs attributes and the current execution context.
 */
class ProcessorIterator implements \Iterator
{
    /** @var array [[processor id, [attribute name => attribute value, ...]], ...] */
    protected array $processors;
    protected ContextInterface $context;
    protected ApplicableCheckerInterface $applicableChecker;
    protected ProcessorRegistryInterface $processorRegistry;
    private ?ParameterBagInterface $applicableCache;
    protected int $index = -1;
    protected int $maxIndex;

    public function __construct(
        array $processors,
        ContextInterface $context,
        ApplicableCheckerInterface $applicableChecker,
        ProcessorRegistryInterface $processorRegistry,
        ?ParameterBagInterface $applicableCache = null
    ) {
        $this->processors = $processors;
        $this->context = $context;
        $this->applicableChecker = $applicableChecker;
        $this->processorRegistry = $processorRegistry;
        $this->applicableCache = $applicableCache;
    }

    /**
     * Gets the applicable checker.
     */
    public function getApplicableChecker(): ApplicableCheckerInterface
    {
        return $this->applicableChecker;
    }

    /**
     * Replaces existing applicable checker.
     */
    public function setApplicableChecker(ApplicableCheckerInterface $applicableChecker): void
    {
        $this->applicableChecker = $applicableChecker;
    }

    /**
     * Gets a action the iterator works with.
     */
    public function getAction(): string
    {
        return $this->context->getAction();
    }

    /**
     * Gets the name of a group the iterator points to.
     */
    public function getGroup(): ?string
    {
        if (-1 === $this->index || !$this->valid()) {
            return null;
        }

        return $this->processors[$this->index][1]['group'] ?? null;
    }

    /**
     * Gets the id of a processor the iterator points to.
     */
    public function getProcessorId(): ?string
    {
        if (-1 === $this->index || !$this->valid()) {
            return null;
        }

        return $this->processors[$this->index][0];
    }

    /**
     * Gets all attributes of a processor the iterator points to.
     *
     * @return array|null [key => value, ...]
     */
    public function getProcessorAttributes(): ?array
    {
        if (-1 === $this->index || !$this->valid()) {
            return null;
        }

        return $this->processors[$this->index][1];
    }

    /**
     * {@inheritDoc}
     */
    public function current(): ProcessorInterface
    {
        return $this->processorRegistry->getProcessor($this->processors[$this->index][0]);
    }

    /**
     * {@inheritDoc}
     */
    public function next(): void
    {
        $this->nextApplicable();
    }

    /**
     * {@inheritDoc}
     */
    public function key(): int
    {
        return $this->index;
    }

    /**
     * {@inheritDoc}
     */
    public function valid(): bool
    {
        return $this->index <= $this->maxIndex;
    }

    /**
     * {@inheritDoc}
     */
    public function rewind(): void
    {
        $this->index = -1;
        $this->maxIndex = \count($this->processors) - 1;
        $this->nextApplicable();
    }

    /**
     * Moves forward to next applicable processor.
     */
    protected function nextApplicable(): void
    {
        $this->index++;
        while ($this->index <= $this->maxIndex) {
            if (ApplicableCheckerInterface::NOT_APPLICABLE !== $this->isApplicable()) {
                break;
            }
            $this->index++;
        }
    }

    /**
     * Checks if the current processor is applicable to be executed.
     */
    protected function isApplicable(): int
    {
        $processorAttributes = $this->processors[$this->index][1];

        if (null === $this->applicableCache) {
            return $this->applicableChecker->isApplicable($this->context, $processorAttributes);
        }

        $cacheKey = $processorAttributes
            ? $this->index . ':' . $this->context->getChecksum()
            : '-:' . $this->context->getChecksum();
        $applicable = $this->applicableCache->get($cacheKey);
        if (null === $applicable) {
            $applicable = $this->applicableChecker->isApplicable($this->context, $processorAttributes);
            $this->applicableCache->set($cacheKey, $applicable);
        }

        return $applicable;
    }
}
