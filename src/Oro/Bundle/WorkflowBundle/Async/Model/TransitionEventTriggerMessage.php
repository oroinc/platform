<?php

namespace Oro\Bundle\WorkflowBundle\Async\Model;

use Oro\Bundle\WorkflowBundle\Entity\EventTriggerInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

use Oro\Component\MessageQueue\Util\JSON;

class TransitionEventTriggerMessage implements \JsonSerializable
{
    const TRANSITION_EVENT_TRIGGER = 'transitionEventTrigger';
    const WORKFLOW_ITEM = 'workflowItem';
    const MAIN_ENTITY = 'mainEntity';

    /** @var int */
    private $triggerId;

    /** @var int */
    private $workflowItemId;

    /** @var mixed */
    private $mainEntityId;

    /**
     * @param int $triggerId
     * @param int $workflowItemId
     * @param mixed $mainEntityId
     */
    protected function __construct($triggerId, $workflowItemId, $mainEntityId)
    {
        $this->triggerId = $triggerId;
        $this->workflowItemId = $workflowItemId;
        $this->mainEntityId = $mainEntityId;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @param string $json
     * @return static
     */
    public static function createFromJson($json)
    {
        $data = JSON::decode($json);
        if (!is_array($data) || !$data) {
            throw new \InvalidArgumentException('Given json should not be empty');
        }

        return new static(
            static::getValue($data, self::TRANSITION_EVENT_TRIGGER),
            static::getValue($data, self::WORKFLOW_ITEM),
            static::getValue($data, self::MAIN_ENTITY)
        );
    }

    /**
     * @param EventTriggerInterface $trigger
     * @param WorkflowItem $item
     * @param mixed $mainEntityId
     * @return static
     */
    public static function create(EventTriggerInterface $trigger, WorkflowItem $item = null, $mainEntityId = null)
    {
        return new static($trigger->getId(), $item ? $item->getId() : null, $mainEntityId);
    }

    /**
     * @return int
     */
    public function getTriggerId()
    {
        return (int)$this->triggerId;
    }

    /**
     * @return int
     */
    public function getWorkflowItemId()
    {
        return $this->workflowItemId;
    }

    /**
     * @return mixed
     */
    public function getMainEntityId()
    {
        return $this->mainEntityId;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            self::TRANSITION_EVENT_TRIGGER => $this->triggerId,
            self::WORKFLOW_ITEM => $this->workflowItemId,
            self::MAIN_ENTITY => $this->mainEntityId
        ];
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
