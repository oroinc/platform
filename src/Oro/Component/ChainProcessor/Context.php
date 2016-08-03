<?php

namespace Oro\Component\ChainProcessor;

class Context extends ParameterBag implements ContextInterface
{
    /** action name */
    const ACTION = 'action';

    /** result data */
    const RESULT = 'result';

    /** @var string|null */
    protected $firstGroup;

    /** @var string|null */
    protected $lastGroup;

    /** @var string[] */
    protected $skippedGroups = [];

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
    public function skipGroup($group)
    {
        if (!in_array($group, $this->skippedGroups, true)) {
            $this->skippedGroups[] = $group;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function undoGroupSkipping($group)
    {
        if (in_array($group, $this->skippedGroups, true)) {
            $this->skippedGroups = array_values(array_diff($this->skippedGroups, [$group]));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasResult()
    {
        return $this->has(self::RESULT);
    }

    /**
     * {@inheritdoc}
     */
    public function getResult()
    {
        return $this->get(self::RESULT);
    }

    /**
     * {@inheritdoc}
     */
    public function setResult($data)
    {
        $this->set(self::RESULT, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function removeResult()
    {
        $this->remove(self::RESULT);
    }
}
