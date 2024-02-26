<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
* Entity that represents Transition Cron Trigger
*
*/
#[ORM\Entity]
class TransitionCronTrigger extends BaseTransitionTrigger
{
    #[ORM\Column(name: 'cron', type: Types::STRING, length: 100)]
    protected ?string $cron = null;

    #[ORM\Column(name: 'filter', type: Types::TEXT, length: 1024, nullable: true)]
    protected ?string $filter = null;

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
    protected function isEqualAdditionalFields(BaseTransitionTrigger $trigger)
    {
        return $trigger instanceof static
            && $this->cron === $trigger->getCron()
            && $this->filter === $trigger->getFilter();
    }
}
