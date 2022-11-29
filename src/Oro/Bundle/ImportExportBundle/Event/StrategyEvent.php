<?php

namespace Oro\Bundle\ImportExportBundle\Event;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Import-Export strategy processing related event.
 */
class StrategyEvent extends Event
{
    const PROCESS_BEFORE = 'oro_importexport.strategy.process_before';
    const PROCESS_AFTER  = 'oro_importexport.strategy.process_after';

    /**
     * @var StrategyInterface
     */
    protected $strategy;

    /**
     * @var object|null
     */
    protected $entity;

    /**
     * @var ContextInterface
     */
    protected $context;

    public function __construct(StrategyInterface $strategy, $entity, ContextInterface $context)
    {
        $this->entity = $entity;
        $this->strategy = $strategy;
        $this->context = $context;
    }

    /**
     * @return StrategyInterface
     */
    public function getStrategy()
    {
        return $this->strategy;
    }

    /**
     * @return object|null
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param object|null $entity
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
    }

    /**
     * @return ContextInterface
     */
    public function getContext()
    {
        return $this->context;
    }
}
