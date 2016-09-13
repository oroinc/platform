<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class TransitionTriggerCron extends AbstractTransitionTrigger
{
    /**
     * @var string
     *
     * @ORM\Column(name="cron", type="string", length=100)
     */
    protected $cron;

    /**
     * @var string
     *
     * @ORM\Column(name="filter", type="text", length=1024, nullable=true)
     */
    protected $filter;

    /**
     * @return string
     */
    public function getCron()
    {
        return $this->cron;
    }

    /**
     * @param string $cron
     * @return $this
     */
    public function setCron($cron)
    {
        $this->cron = $cron;

        return $this;
    }

    /**
     * @return string
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @param string $filter
     * @return $this
     */
    public function setFilter($filter)
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * @param TransitionTriggerCron $trigger
     * @return $this
     */
    public function import(TransitionTriggerCron $trigger)
    {
        $this->importMainData($trigger);

        $this->setCron($trigger->getCron())
            ->setFilter($trigger->getFilter());

        return $this;
    }

    public function isEqualTo(AbstractTransitionTrigger $trigger)
    {
        if (!$trigger instanceof static) {
            return false;
        }

        return $this->cron === $trigger->cron
        && $this->workflowDefinition === $trigger->workflowDefinition
        && $this->queued === $trigger->queued
        && $this->filter === $trigger->filter
        && $this->transitionName === $trigger->transitionName;
    }
}
