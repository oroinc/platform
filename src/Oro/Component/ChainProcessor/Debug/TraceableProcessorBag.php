<?php

namespace Oro\Component\ChainProcessor\Debug;

use Oro\Component\ChainProcessor\ApplicableCheckerInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorBagInterface;

class TraceableProcessorBag implements ProcessorBagInterface
{
    /** @var ProcessorBagInterface */
    protected $processorBag;

    /** @var TraceLogger */
    protected $logger;

    /**
     * @param ProcessorBagInterface $processorBag
     * @param TraceLogger           $logger
     */
    public function __construct(ProcessorBagInterface $processorBag, TraceLogger $logger)
    {
        $this->processorBag = $processorBag;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function addGroup($group, $action, $priority = 0)
    {
        $this->processorBag->addGroup($group, $action, $priority);
    }

    /**
     * {@inheritdoc}
     */
    public function addProcessor($processorId, array $attributes, $action = null, $group = null, $priority = 0)
    {
        $this->processorBag->addProcessor($processorId, $attributes, $action, $group, $priority);
    }

    /**
     * {@inheritdoc}
     */
    public function addApplicableChecker(ApplicableCheckerInterface $checker, $priority = 0)
    {
        $this->processorBag->addApplicableChecker($checker, $priority);
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessors(ContextInterface $context)
    {
        return $this->processorBag->getProcessors($context);
    }

    /**
     * {@inheritdoc}
     */
    public function getActions()
    {
        return $this->processorBag->getActions();
    }

    /**
     * {@inheritdoc}
     */
    public function getActionGroups($action)
    {
        return $this->processorBag->getActionGroups($action);
    }
}
