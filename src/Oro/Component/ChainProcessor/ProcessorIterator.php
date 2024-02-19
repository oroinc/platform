<?php

namespace Oro\Component\ChainProcessor;

/**
 * Iterates over processors filtered by matching theirs attributes and the current execution context.
 */
class ProcessorIterator implements \Iterator
{
    /** @var array [[processor id, [attribute name => attribute value, ...]], ...] */
    protected $processors;

    /** @var ContextInterface */
    protected $context;

    /** @var ApplicableCheckerInterface */
    protected $applicableChecker;

    /** @var ProcessorRegistryInterface */
    protected $processorRegistry;

    /** @var ParameterBagInterface|null */
    private $applicableCache;

    /** @var int */
    protected $index;

    /** @var int */
    protected $maxIndex;

    public function __construct(
        array $processors,
        ContextInterface $context,
        ApplicableCheckerInterface $applicableChecker,
        ProcessorRegistryInterface $processorRegistry
    ) {
        $this->processors = $processors;
        $this->context = $context;
        $this->applicableChecker = $applicableChecker;
        $this->processorRegistry = $processorRegistry;
    }

    public function setApplicableCache(?ParameterBagInterface $applicableCache): void
    {
        $this->applicableCache = $applicableCache;
    }

    /**
     * Gets the applicable checker.
     *
     * @return ApplicableCheckerInterface
     */
    public function getApplicableChecker()
    {
        return $this->applicableChecker;
    }

    /**
     * Replaces existing applicable checker.
     */
    public function setApplicableChecker(ApplicableCheckerInterface $applicableChecker)
    {
        $this->applicableChecker = $applicableChecker;
    }

    /**
     * Gets a action the iterator works with.
     *
     * @return string
     */
    public function getAction()
    {
        return $this->context->getAction();
    }

    /**
     * Gets the name of a group the iterator points to.
     *
     * @return string|null
     */
    public function getGroup()
    {
        if (-1 === $this->index || !$this->valid()) {
            return null;
        }

        return $this->processors[$this->index][1]['group'] ?? null;
    }

    /**
     * Gets the id of a processor the iterator points to.
     *
     * @return string|null
     */
    public function getProcessorId()
    {
        if (-1 === $this->index || !$this->valid()) {
            return null;
        }

        return $this->processors[$this->index][0];
    }

    /**
     * Gets all attributes of a processor the iterator points to.
     *
     * @return array [key => value, ...]
     */
    public function getProcessorAttributes()
    {
        if (-1 === $this->index || !$this->valid()) {
            return null;
        }

        return $this->processors[$this->index][1];
    }

    /**
     * {@inheritdoc}
     */
    public function current(): ProcessorInterface
    {
        return $this->processorRegistry->getProcessor($this->processors[$this->index][0]);
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        $this->nextApplicable();
    }

    /**
     * {@inheritdoc}
     */
    public function key(): mixed
    {
        return $this->index;
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return $this->index <= $this->maxIndex;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->index = -1;
        $this->maxIndex = \count($this->processors) - 1;
        $this->nextApplicable();
    }

    /**
     * Moves forward to next applicable processor
     */
    protected function nextApplicable()
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
