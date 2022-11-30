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

    /** @var int */
    protected $triggerId;

    /** @var array|string|int|null */
    protected $mainEntityId;

    /**
     * @param int $triggerId
     * @param array|string|int|null $mainEntityId
     */
    protected function __construct($triggerId, $mainEntityId)
    {
        $this->triggerId = $triggerId;
        $this->mainEntityId = $mainEntityId;
    }

    /**
     * @param BaseTransitionTrigger $trigger
     * @param array|string|int|null $mainEntityId
     * @return static
     */
    public static function create(BaseTransitionTrigger $trigger, $mainEntityId = null)
    {
        return new static($trigger->getId(), $mainEntityId);
    }

    public static function createFromArray(array $data): static
    {
        return new static($data[self::TRANSITION_TRIGGER] ?? null, $data[self::MAIN_ENTITY] ?? null);
    }

    /**
     * @param string $json
     * @return static
     * @throws \InvalidArgumentException
     */
    public static function createFromJson($json)
    {
        $data = self::jsonToArray($json);

        return new static(
            static::getValue($data, self::TRANSITION_TRIGGER),
            static::getValue($data, self::MAIN_ENTITY)
        );
    }

    /**
     * @return int
     */
    public function getTriggerId()
    {
        return (int)$this->triggerId;
    }

    /**
     * @return array|string|int|null
     */
    public function getMainEntityId()
    {
        return $this->mainEntityId;
    }

    /**
     * return array
     */
    public function toArray()
    {
        return [self::TRANSITION_TRIGGER => $this->triggerId, self::MAIN_ENTITY => $this->mainEntityId];
    }

    /**
     * @param string $json
     * @return array
     * @throws \InvalidArgumentException
     */
    protected static function jsonToArray($json)
    {
        $data = JSON::decode($json);
        if (!is_array($data) || !$data) {
            throw new \InvalidArgumentException('Given json should not be empty');
        }

        return $data;
    }

    /**
     * @param array $array
     * @param string $key
     * @return mixed|null
     */
    protected static function getValue(array $array, $key)
    {
        return array_key_exists($key, $array) ? $array[$key] : null;
    }
}
