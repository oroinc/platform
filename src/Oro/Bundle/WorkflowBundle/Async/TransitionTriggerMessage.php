<?php

namespace Oro\Bundle\WorkflowBundle\Async;

use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;

/**
 * Transition trigger message DTO.
 */
class TransitionTriggerMessage
{
    public const TRANSITION_TRIGGER = 'transitionTrigger';
    public const MAIN_ENTITY = 'mainEntity';

    protected ?int $triggerId;

    protected array|string|int|null $mainEntityId;

    protected function __construct(?int $triggerId, array|string|int|null $mainEntityId)
    {
        $this->triggerId = $triggerId;
        $this->mainEntityId = $mainEntityId;
    }

    public static function create(BaseTransitionTrigger $trigger, array|string|int|null $mainEntityId = null): static
    {
        return new static($trigger->getId(), $mainEntityId);
    }

    public static function createFromArray(array $data): static
    {
        return new static($data[self::TRANSITION_TRIGGER] ?? null, $data[self::MAIN_ENTITY] ?? null);
    }

    public function getTriggerId(): ?int
    {
        return $this->triggerId;
    }

    public function getMainEntityId(): array|string|int|null
    {
        return $this->mainEntityId;
    }

    public function toArray(): array
    {
        return [self::TRANSITION_TRIGGER => $this->triggerId, self::MAIN_ENTITY => $this->mainEntityId];
    }
}
