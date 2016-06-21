<?php

namespace Oro\Component\ChainProcessor;

class ProcessorIterator implements \Iterator
{
    /**
     * @var array
     *  [
     *      action => [
     *          [
     *              'processor'  => processorId,
     *              'attributes' => [key => value, ...]
     *          ],
     *          ...
     *      ],
     *      ...
     *  ]
     */
    protected $processors;

    /** @var ContextInterface */
    protected $context;

    /** @var ApplicableCheckerInterface */
    protected $applicableChecker;

    /** @var ProcessorFactoryInterface */
    protected $processorFactory;

    /** @var string */
    protected $action;

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
        $this->processors        = $processors;
        $this->context           = $context;
        $this->applicableChecker = $applicableChecker;
        $this->processorFactory  = $processorFactory;
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
        return $this->action;
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

        $attributes = $this->processors[$this->action][$this->index]['attributes'];

        return isset($attributes['group'])
            ? $attributes['group']
            : null;
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

        return $this->processors[$this->action][$this->index]['processor'];
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

        return $this->processors[$this->action][$this->index]['attributes'];
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        $processorId = $this->processors[$this->action][$this->index]['processor'];
        $processor   = $this->processorFactory->getProcessor($processorId);
        if (null === $processor) {
            throw new \RuntimeException(sprintf('The processor "%s" does not exist.', $processorId));
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
        $this->action   = $this->context->getAction();
        $this->index    = -1;
        $this->maxIndex = isset($this->processors[$this->action])
            ? count($this->processors[$this->action]) - 1
            : -1;
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
                $this->processors[$this->action][$this->index]['attributes']
            );
            if ($applicable !== ApplicableCheckerInterface::NOT_APPLICABLE) {
                break;
            }
            $this->index++;
        }
    }
}
