<?php

namespace Oro\Bundle\WorkflowBundle\Event\Transition;

use Doctrine\Common\Collections\Collection;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Workflow event that is triggered before the workflow transition assemble.
 * Allows to alter transition configuration.
 */
final class TransitionAssembleEvent extends Event
{
    public const NAME = 'oro_workflow.transition.assemble';

    public function __construct(
        private string $transitionName,
        private array $options,
        private array $definition,
        private array|Collection $steps,
        private array|Collection $attributes
    ) {
    }

    public function getTransitionName(): string
    {
        return $this->transitionName;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function getDefinition(): array
    {
        return $this->definition;
    }

    public function getSteps(): Collection|array
    {
        return $this->steps;
    }

    public function getAttributes(): Collection|array
    {
        return $this->attributes;
    }
}
