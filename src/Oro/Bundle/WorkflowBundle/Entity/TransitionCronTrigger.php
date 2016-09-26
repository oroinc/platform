<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class TransitionCronTrigger extends AbstractTransitionTrigger
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
     * @param TransitionCronTrigger $trigger
     * @return $this
     */
    public function import(TransitionCronTrigger $trigger)
    {
        $this->importMainData($trigger);

        $this->setCron($trigger->getCron())
            ->setFilter($trigger->getFilter());

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf(
            'cron: [%s:%s](%s):%s:%s',
            $this->workflowDefinition ? $this->workflowDefinition->getName() : 'null',
            $this->transitionName,
            $this->cron,
            $this->filter,
            $this->queued ? 'MQ' : 'RUNTIME'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function isEqualAdditionalFields(AbstractTransitionTrigger $trigger)
    {
        return $trigger instanceof static
            && $this->cron === $trigger->getCron()
            && $this->filter === $trigger->getFilter();
    }
}
