<?php

namespace Oro\Component\ChainProcessor;

/**
 * The base implementation of an execution context for processors.
 */
class Context extends ParameterBag implements ContextInterface
{
    /** action name */
    public const ACTION = 'action';

    /** @var string|null */
    private $firstGroup;

    /** @var string|null */
    private $lastGroup;

    /** @var string[] */
    private $skippedGroups = [];

    /** @var mixed */
    private $result;

    /** @var bool */
    private $resultExists = false;

    /**
     * {@inheritdoc}
     */
    public function getAction()
    {
        return $this->get(self::ACTION);
    }

    /**
     * {@inheritdoc}
     */
    public function setAction($action)
    {
        $this->set(self::ACTION, $action);
    }

    /**
     * {@inheritdoc}
     */
    public function getFirstGroup()
    {
        return $this->firstGroup;
    }

    /**
     * {@inheritdoc}
     */
    public function setFirstGroup($group)
    {
        $this->firstGroup = $group;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastGroup()
    {
        return $this->lastGroup;
    }

    /**
     * {@inheritdoc}
     */
    public function setLastGroup($group)
    {
        $this->lastGroup = $group;
    }

    /**
     * {@inheritdoc}
     */
    public function hasSkippedGroups()
    {
        return !empty($this->skippedGroups);
    }

    /**
     * {@inheritdoc}
     */
    public function getSkippedGroups()
    {
        return $this->skippedGroups;
    }

    /**
     * {@inheritdoc}
     */
    public function resetSkippedGroups()
    {
        $this->skippedGroups = [];
    }

    /**
     * {@inheritdoc}
     */
    public function skipGroup($group)
    {
        if (!\in_array($group, $this->skippedGroups, true)) {
            $this->skippedGroups[] = $group;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function undoGroupSkipping($group)
    {
        $key = \array_search($group, $this->skippedGroups, true);
        if (false !== $key) {
            unset($this->skippedGroups[$key]);
            $this->skippedGroups = \array_values($this->skippedGroups);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasResult()
    {
        return $this->resultExists;
    }

    /**
     * {@inheritdoc}
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * {@inheritdoc}
     */
    public function setResult($data)
    {
        $this->result = $data;
        $this->resultExists = true;
    }

    /**
     * {@inheritdoc}
     */
    public function removeResult()
    {
        $this->result = null;
        $this->resultExists = false;
    }
}
