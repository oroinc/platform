<?php

namespace Oro\Bundle\LayoutBundle\Layout\Form;

use Oro\Component\Layout\BlockBuilderInterface;

class GroupingFormLayoutBuilder extends FormLayoutBuilder
{
    /** @var array */
    protected $groups;

    /** @var array */
    protected $fieldToGroupMap;

    /** @var array */
    protected $defaultGroupName;

    /**
     * {@inheritdoc}
     */
    protected function initializeState(
        FormAccessorInterface $formAccessor,
        BlockBuilderInterface $builder,
        array $options
    ) {
        parent::initializeState($formAccessor, $builder, $options);
        $this->groups          = $options['groups'];
        $this->fieldToGroupMap = [];
        foreach ($this->groups as $name => $group) {
            $this->groups[$name]['id']        = $options['form_group_prefix'] . $name;
            $this->groups[$name]['hasFields'] = false;
            if (isset($group['default']) && $group['default']) {
                $this->defaultGroupName = $name;
            }
            if (isset($group['fields'])) {
                foreach ($group['fields'] as $fieldPath) {
                    $this->fieldToGroupMap[$fieldPath] = $name;
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function clearState()
    {
        parent::clearState();
        $this->groups           = null;
        $this->fieldToGroupMap  = null;
        $this->defaultGroupName = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function doBuild()
    {
        parent::doBuild();
        $this->addGroups();
    }

    /**
     * {@inheritdoc}
     */
    protected function addField($fieldPath, $id, $parentId = null)
    {
        $groupName = $this->findGroupName($fieldPath);
        if ($groupName) {
            $this->groups[$groupName]['hasFields'] = true;

            $parentId = $this->groups[$groupName]['id'];
        }
        parent::addField($fieldPath, $id, $parentId);
    }

    /**
     * Add all groups to the layout.
     */
    protected function addGroups()
    {
        foreach ($this->groups as $group) {
            if ($group['hasFields']) {
                $this->addGroup($group);
            }
        }
    }

    /**
     * Add the given group to the layout.
     *
     * @param array $group
     */
    protected function addGroup($group)
    {
        $this->layoutManipulator->add(
            $group['id'],
            isset($group['parentId']) ? $group['parentId'] : $this->builder->getId(),
            'fieldset',
            ['title' => isset($group['title']) ? $group['title'] : '']
        );
    }

    /**
     * Returns the name of a group the given field should be added.
     *
     * @param string $fieldPath
     *
     * @return string|null
     */
    protected function findGroupName($fieldPath)
    {
        $groupName = null;
        while ($fieldPath) {
            if (isset($this->fieldToGroupMap[$fieldPath])) {
                $groupName = $this->fieldToGroupMap[$fieldPath];
                break;
            }
            $fieldPath = $this->getParentFieldPath($fieldPath);
        }

        if (!$groupName && $this->defaultGroupName) {
            $groupName = $this->defaultGroupName;
        }

        return $groupName;
    }
}
