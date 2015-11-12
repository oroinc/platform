<?php

namespace Oro\Component\ChainProcessor;

class Context extends ParameterBag implements ContextInterface
{
    /** action name */
    const ACTION = 'action';

    /** result data */
    const RESULT = 'result';

    /** a group starting from which processors should be executed */
    const FIRST_GROUP = 'firstGroup';

    /** a group after which processors should not be executed */
    const LAST_GROUP = 'lastGroup';

    /** a list of groups to be skipped */
    const SKIPPED_GROUPS = 'skippedGroups';

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
        return $this->get(self::FIRST_GROUP);
    }

    /**
     * {@inheritdoc}
     */
    public function setFirstGroup($group)
    {
        $this->set(self::FIRST_GROUP, $group);
    }

    /**
     * {@inheritdoc}
     */
    public function getLastGroup()
    {
        return $this->get(self::LAST_GROUP);
    }

    /**
     * {@inheritdoc}
     */
    public function setLastGroup($group)
    {
        $this->set(self::LAST_GROUP, $group);
    }

    /**
     * {@inheritdoc}
     */
    public function hasSkippedGroups()
    {
        return $this->has(self::SKIPPED_GROUPS);
    }

    /**
     * {@inheritdoc}
     */
    public function getSkippedGroups()
    {
        $groups = $this->get(self::SKIPPED_GROUPS);

        return null !== $groups
            ? $groups
            : [];
    }

    /**
     * {@inheritdoc}
     */
    public function skipGroup($group)
    {
        $groups = $this->get(self::SKIPPED_GROUPS);
        if (null === $groups) {
            $this->set(self::SKIPPED_GROUPS, [$group]);
        } elseif (!in_array($group, $groups, true)) {
            $groups[] = $group;
            $this->set(self::SKIPPED_GROUPS, $groups);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function undoGroupSkipping($group)
    {
        $groups = $this->get(self::SKIPPED_GROUPS);
        if (null !== $groups && in_array($group, $groups, true)) {
            $groups = array_values(array_diff($groups, [$group]));
            if (empty($groups)) {
                $this->remove(self::SKIPPED_GROUPS);
            } else {
                $this->set(self::SKIPPED_GROUPS, $groups);
            }
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
