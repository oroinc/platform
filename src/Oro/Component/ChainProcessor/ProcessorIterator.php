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

    /** @var ProcessorFactoryInterface */
    protected $processorFactory;

    /** @var int */
    protected $index;

    /** @var int */
    protected $maxIndex;

    /**
     * @param array                      $processors
     * @param ContextInterface           $context
     * @param ApplicableCheckerInterface $applicableChecker
     * @param ProcessorFactoryInterface  $processorFactory
     */
    public function __construct(
        array $processors,
        ContextInterface $context,
        ApplicableCheckerInterface $applicableChecker,
        ProcessorFactoryInterface $processorFactory
    ) {
        $this->processors = $processors;
        $this->context = $context;
        $this->applicableChecker = $applicableChecker;
        $this->processorFactory = $processorFactory;
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
     *
     * @param ApplicableCheckerInterface $applicableChecker
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
    public function current()
    {
        $processorId = $this->processors[$this->index][0];
        $processor = $this->processorFactory->getProcessor($processorId);
        if (null === $processor) {
            throw new \RuntimeException(\sprintf('The processor "%s" does not exist.', $processorId));
        }

        return $processor;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->nextApplicable();
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->index;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->index <= $this->maxIndex;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
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
            $applicable = $this->applicableChecker->isApplicable(
                $this->context,
                $this->processors[$this->index][1]
            );
            if (ApplicableCheckerInterface::NOT_APPLICABLE !== $applicable) {
                break;
            }
            $this->index++;
        }
    }
}
