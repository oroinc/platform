<?php

namespace Oro\Bundle\WorkflowBundle\Async\Model;

use Oro\Bundle\WorkflowBundle\Entity\EventTriggerInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

use Oro\Component\MessageQueue\Util\JSON;

class TransitionEventTriggerMessage implements \JsonSerializable
{
    const TRANSITION_EVENT_TRIGGER = 'transitionEventTrigger';
    const WORKFLOW_ITEM = 'workflowItem';

    /** @var int */
    private $triggerId;

    /** @var int */
    private $workflowItemId;

    /**
     * @param int $triggerId
     * @param int $workflowItemId
     */
    protected function __construct($triggerId, $workflowItemId)
    {
        $this->triggerId = $triggerId;
        $this->workflowItemId = $workflowItemId;
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
            static::getValue($data, self::WORKFLOW_ITEM)
        );
    }

    /**
     * @param EventTriggerInterface $trigger
     * @param WorkflowItem $item
     * @return static
     */
    public static function create(EventTriggerInterface $trigger, WorkflowItem $item)
    {
        return new static($trigger->getId(), $item->getId());
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
        return (int)$this->workflowItemId;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            self::TRANSITION_EVENT_TRIGGER => $this->triggerId,
            self::WORKFLOW_ITEM => $this->workflowItemId
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
