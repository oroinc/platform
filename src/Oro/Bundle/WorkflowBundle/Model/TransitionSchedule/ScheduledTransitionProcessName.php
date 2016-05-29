<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionSchedule;

class ScheduledTransitionProcessName
{
    const IDENTITY_SUFFIX = 'stpn';
    const DELIMITER       = '__';

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

        if (!array_key_exists(2, $chunks) || $chunks[2] !== self::IDENTITY_SUFFIX) {
            throw new \InvalidArgumentException(
                sprintf('Can not restore. Provided name `%s` is not valid `%s` representation.', $name, __CLASS__)
            );
        }

        list($workflow, $transition) = $chunks;

        return new self($workflow, $transition);
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
            throw new \UnderflowException('Cannot build valid string representation without all parts.');
        }

        return implode(self::DELIMITER, [$this->workflow, $this->transition, self::IDENTITY_SUFFIX]);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }
}
