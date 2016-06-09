<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionSchedule;

class ScheduledTransitionProcessName
{
    const IDENTITY_PREFIX = 'stpn';
    const DELIMITER = '__';

    /** @var string */
    private $workflow;

    /** @var string */
    private $transition;

    /**
     * @param string $workflow
     * @param string $transition
     */
    public function __construct($workflow, $transition)
    {
        $this->workflow = (string)$workflow;
        $this->transition = (string)$transition;
    }

    /**
     * @return string
     */
    public function getTransitionName()
    {
        return $this->transition;
    }

    /**
     * @return string
     */
    public function getWorkflowName()
    {
        return $this->workflow;
    }

    /**
     * @return string
     * @throws \UnderflowException
     */
    public function getName()
    {
        if (empty($this->transition) || empty($this->workflow)) {
            throw new \UnderflowException(
                'Cannot build valid string representation of scheduled transition process name without all parts.'
            );
        }

        return implode(self::DELIMITER, [self::IDENTITY_PREFIX, $this->workflow, $this->transition]);
    }
}
