<?php

namespace Oro\Component\ChainProcessor;

/**
 * Provides a set of methos to build the ProcessorBag configuration.
 */
class ProcessorBagConfigBuilder implements ProcessorBagConfigProviderInterface
{
    /**
     * @var array|null
     * after the configuration is built this property is set to NULL,
     * this switches the builder in "frozen" state and further modification of it is prohibited
     */
    private $initialData;

    /** @var array [action => [group, ...], ...] */
    private $groups;

    /** @var array [action => [[processor id, [attribute name => attribute value, ...]], ...], ...] */
    private $processors;

    /**
     * @param array $groups     [action => [group => priority, ...], ...]
     * @param array $processors [action => [priority => [[processor id, [attribute => value, ...]], ...], ...], ...]
     */
    public function __construct(array $groups = [], array $processors = [])
    {
        $this->initialData = [
            'groups'     => $groups,
            'processors' => $processors
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getGroups()
    {
        if (null !== $this->initialData) {
            $this->build();
        }

        return $this->groups;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessors()
    {
        if (null !== $this->initialData) {
            $this->build();
        }

        return $this->processors;
    }

    /**
     * Registers a processing group.
     *
     * @param string $group
     * @param string $action
     * @param int    $priority
     */
    public function addGroup($group, $action, $priority = 0)
    {
        $this->assertNotFrozen();

        $this->initialData['groups'][$action][$group] = $priority;
    }

    /**
     * Registers a processor.
     *
     * @param string      $processorId
     * @param array       $attributes
     * @param string|null $action
     * @param string|null $group
     * @param int         $priority
     */
    public function addProcessor($processorId, array $attributes, $action = null, $group = null, $priority = 0)
    {
        $this->assertNotFrozen();

        if (null === $action) {
            $action = '';
        }
        if (!empty($group)) {
            $attributes['group'] = $group;
        }

        $this->initialData['processors'][$action][$priority][] = [$processorId, $attributes];
    }

    /**
     * Checks whether the processor bag can be modified
     */
    public function assertNotFrozen()
    {
        if (null === $this->initialData) {
            throw new \RuntimeException('The ProcessorBag is frozen.');
        }
    }

    /**
     * Initializes $this->processors
     */
    private function build()
    {
        $this->processors = [];
        $this->groups = [];

        if (!empty($this->initialData['processors'])) {
            $groups = [];
            if (!empty($this->initialData['groups'])) {
                $groups = $this->initialData['groups'];
            }

            $startCommonProcessors = [];
            $endCommonProcessors = [];
            if (!empty($this->initialData['processors'][''])) {
                foreach ($this->initialData['processors'][''] as $priority => $priorityData) {
                    foreach ($priorityData as $processor) {
                        if ($priority < 0) {
                            $endCommonProcessors[$priority][] = $processor;
                        } else {
                            $startCommonProcessors[$priority][] = $processor;
                        }
                    }
                }
                $startCommonProcessors = $this->sortByPriorityAndFlatten($startCommonProcessors);
                $endCommonProcessors = $this->sortByPriorityAndFlatten($endCommonProcessors);
                unset($this->initialData['processors']['']);
            }

            foreach ($this->initialData['processors'] as $action => $actionData) {
                $this->processors[$action] = array_merge(
                    $startCommonProcessors,
                    $this->getSortedProcessors($action, $actionData, $groups),
                    $endCommonProcessors
                );
            }
        }

        $this->initialData = null;
    }

    /**
     * @param string $action
     * @param array  $actionData
     * @param array  $groups
     *
     * @return array
     */
    private function getSortedProcessors($action, $actionData, $groups)
    {
        $processors = [];
        $processorGroups = [];
        foreach ($actionData as $priority => $priorityData) {
            foreach ($priorityData as $processor) {
                if (isset($processor[1]['group'])) {
                    $group = $processor[1]['group'];
                    if (!isset($groups[$action][$group])) {
                        throw new \RuntimeException(
                            sprintf(
                                'The group "%s" is not defined. Processor: "%s".',
                                $group,
                                $processor[0]
                            )
                        );
                    }
                    $groupPriority = $groups[$action][$group];
                    $processorPriority = self::calculatePriority($priority, $groupPriority);
                    if (!isset($processorGroups[$group])) {
                        $processorGroups[$group] = $groupPriority;
                    }
                } else {
                    $processorPriority = self::calculatePriority($priority);
                }
                $processors[$processorPriority][] = $processor;
            }
        }
        $processors = $this->sortByPriorityAndFlatten($processors);

        arsort($processorGroups);
        $this->groups[$action] = array_keys($processorGroups);

        return $processors;
    }

    /**
     * Sorts the given groups of items by priority and returns flatten array of sorted items.
     * The higher the priority, the earlier item is added to the result array.
     *
     * @param array $items [priority => [item, ...], ...]
     *
     * @return array [item, ...]
     */
    private function sortByPriorityAndFlatten(array $items)
    {
        if (empty($items)) {
            return [];
        }

        krsort($items);
        $items = call_user_func_array('array_merge', $items);

        return $items;
    }

    /**
     * Calculates an internal priority of a processor based on its priority and a priority of its group.
     *
     * @param int      $processorPriority
     * @param int|null $groupPriority
     *
     * @return int
     */
    private static function calculatePriority($processorPriority, $groupPriority = null)
    {
        if (null === $groupPriority) {
            if ($processorPriority < 0) {
                $processorPriority += self::getIntervalPriority(-255, -255) + 1;
            } else {
                $processorPriority += self::getIntervalPriority(255, 255) + 2;
            }
        } else {
            if ($groupPriority < -255 || $groupPriority > 255) {
                throw new \RangeException(
                    sprintf(
                        'The value %d is not valid priority of a group. It must be between -255 and 255.',
                        $groupPriority
                    )
                );
            }
            if ($processorPriority < -255 || $processorPriority > 255) {
                throw new \RangeException(
                    sprintf(
                        'The value %d is not valid priority of a processor. It must be between -255 and 255.',
                        $processorPriority
                    )
                );
            }
        }

        return self::getIntervalPriority($processorPriority, $groupPriority);
    }

    /**
     * @param int $processorPriority
     * @param int $groupPriority
     *
     * @return int
     */
    private static function getIntervalPriority($processorPriority, $groupPriority)
    {
        return ($groupPriority * 511) + $processorPriority - 1;
    }
}
