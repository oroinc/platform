<?php

namespace Oro\Bundle\ImportExportBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface;

class StrategyEvent extends Event
{
    const PROCESS_BEFORE = 'oro_importexport.strategy.process_before';
    const PROCESS_AFTER  = 'oro_importexport.strategy.process_after';

    /**
     * @var StrategyInterface
     */
    protected $strategy;

    /**
     * @var object
     */
    protected $entity;

    /**
     * @param StrategyInterface $strategy
     * @param $entity
     */
    public function __construct(StrategyInterface $strategy, $entity)
    {
        $this->entity = $entity;
        $this->strategy = $strategy;
    }

    /**
     * @return StrategyInterface
     */
    public function getStrategy()
    {
        return $this->strategy;
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param object $entity
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
    }
}
