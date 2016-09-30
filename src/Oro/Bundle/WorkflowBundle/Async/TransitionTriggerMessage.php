<?php

namespace Oro\Bundle\WorkflowBundle\Async;

use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;

use Oro\Component\MessageQueue\Util\JSON;

final class TransitionTriggerMessage
{
    const TRANSITION_TRIGGER = 'transitionTrigger';
    const MAIN_ENTITY = 'mainEntity';

    /** @var int */
    protected $triggerId;

    /** @var mixed */
    protected $mainEntityId;

    /**
     * @param int $triggerId
     * @param mixed $mainEntityId
     */
    protected function __construct($triggerId, $mainEntityId)
    {
        $this->triggerId = $triggerId;
        $this->mainEntityId = $mainEntityId;
    }

    /**
     * @param BaseTransitionTrigger $trigger
     * @param mixed $mainEntityId
     * @return static
     */
    public static function create(BaseTransitionTrigger $trigger, $mainEntityId = null)
    {
        return new static($trigger->getId(), $mainEntityId);
    }

    /**
     * @param string $json
     * @return static
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
     * @return mixed
     */
    public function getMainEntityId()
    {
        return $this->mainEntityId;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return [self::TRANSITION_TRIGGER => $this->triggerId, self::MAIN_ENTITY => $this->mainEntityId];
    }

    /**
     * @param string $json
     * @return array
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
