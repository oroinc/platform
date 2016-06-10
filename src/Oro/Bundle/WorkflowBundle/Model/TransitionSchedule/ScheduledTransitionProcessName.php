<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionSchedule;

class ScheduledTransitionProcessName
{
    const IDENTITY_PREFIX = 'stpn';
    const DELIMITER = '__';

    /** @var string */
    private $workflowName;

    /** @var string */
    private $transitionName;

    /**
     * @param string $workflowName
     * @param string $transitionName
     */
    public function __construct($workflowName, $transitionName)
    {
        $this->workflow = (string)$workflowName;
        $this->transition = (string)$transitionName;
    }

    /**
     * @return string
     */
    public function getTransitionName()
    {
        return $this->transitionName;
    }

    /**
     * @return string
     */
    public function getWorkflowName()
    {
        return $this->workflowName;
    }

    /**
     * @return string
     * @throws \UnderflowException
     */
    public function getName()
    {
        if (empty($this->transitionName) || empty($this->workflowName)) {
            throw new \UnderflowException(
                'Cannot build valid string representation of scheduled transition process name without all parts.'
            );
        }

        return implode(self::DELIMITER, [self::IDENTITY_PREFIX, $this->workflowName, $this->transitionName]);
    }
}
