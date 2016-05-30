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
     * @param string $name
     * @return ScheduledTransitionProcessName
     */
    public static function restore($name)
    {
        $chunks = explode(self::DELIMITER, (string)$name);

        if (!array_key_exists(0, $chunks) || $chunks[0] !== self::IDENTITY_PREFIX) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Can not restore name object. Provided name `%s` is not valid `%s` representation.',
                    $name,
                    __CLASS__
                )
            );
        }

        return new self($chunks[1], $chunks[2]);
    }

    /**
     * @return string
     */
    public function getTransition()
    {
        return $this->transition;
    }

    /**
     * @return string
     */
    public function getWorkflow()
    {
        return $this->workflow;
    }

    /**
     * @return string
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

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }
}
