<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException;

/**
 * Workflow transition manager.
 */
class TransitionManager
{
    public const DEFAULT_START_TRANSITION_NAME = '__start__';

    /**
     * @var Collection
     */
    protected $transitions;

    /**
     * @param Collection|Transition[] $transitions
     */
    public function __construct($transitions = null)
    {
        $this->setTransitions($transitions);
    }

    /**
     * @return Transition[]|Collection
     */
    public function getTransitions()
    {
        return $this->transitions;
    }

    /**
     * @param string $transitionName
     * @return Transition|null
     */
    public function getTransition($transitionName)
    {
        return $this->transitions->get($transitionName);
    }

    /**
     * @param Transition[]|Collection $transitions
     * @return TransitionManager
     */
    public function setTransitions($transitions)
    {
        $data = array();
        if ($transitions) {
            foreach ($transitions as $transition) {
                $data[$transition->getName()] = $transition;
            }
            unset($transitions);
        }
        $this->transitions = new ArrayCollection($data);

        return $this;
    }

    /**
     * Check transition argument type.
     *
     * @param string|Transition $transition
     * @throws \InvalidArgumentException
     */
    protected function assertTransitionArgument($transition)
    {
        if (!is_string($transition) && !$transition instanceof Transition) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expected transition argument type is string or Transition, but %s given',
                    is_object($transition) ? get_class($transition) : gettype($transition)
                )
            );
        }
    }

    /**
     * Receive transition by name or object
     *
     * @param string|Transition $transition
     * @return Transition
     * @throws InvalidTransitionException
     */
    public function extractTransition($transition)
    {
        $this->assertTransitionArgument($transition);
        if (is_string($transition)) {
            $transitionName = $transition;
            $transition = $this->getTransition($transitionName);
            if (!$transition) {
                throw InvalidTransitionException::unknownTransition($transitionName);
            }
        }

        return $transition;
    }

    /**
     * @param string $transition
     * @return TransitionManager|null
     */
    public function getStartTransition($transition)
    {
        if (is_string($transition)) {
            $transition = $this->getTransition($transition);
        }

        if (!$transition) {
            $transition = $this->getDefaultStartTransition();
        }

        return $transition instanceof Transition && $transition->isStart() ? $transition : null;
    }

    /**
     * Get start transitions
     *
     * @return Collection|Transition[]
     */
    public function getStartTransitions()
    {
        return $this->transitions->filter(
            function (Transition $transition) {
                return $transition->isStart();
            }
        );
    }

    /**
     * @return null|Transition
     */
    public function getDefaultStartTransition()
    {
        return $this->getTransition(self::DEFAULT_START_TRANSITION_NAME);
    }
}
