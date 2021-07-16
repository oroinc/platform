<?php

namespace Oro\Component\ChainProcessor;

/**
 * Provides a set of methods to build the ProcessorBag configuration.
 */
class ProcessorBagConfigBuilder implements ProcessorBagConfigProviderInterface
{
    private const GROUPS          = 'groups';
    private const PROCESSORS      = 'processors';
    private const NO_ACTION       = '';
    private const GROUP_ATTRIBUTE = 'group';

    /**
     * @var array|null
     * after the configuration is built this property is set to NULL,
     * this switches the builder in "frozen" state and further modification of it is prohibited
     */
    private $initialData;

    /** @var array [action => [group, ... (groups are ordered by priority)], ...] */
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
            self::GROUPS     => $groups,
            self::PROCESSORS => $processors
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getActions(): array
    {
        $this->ensureInitialized();

        return array_keys($this->processors);
    }

    /**
     * {@inheritdoc}
     */
    public function getGroups(string $action): array
    {
        $this->ensureInitialized();

        return $this->groups[$action] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessors(string $action): array
    {
        $this->ensureInitialized();

        return $this->processors[$action] ?? [];
    }

    /**
     * @return array [action => [group, ...], ...]
     */
    public function getAllGroups(): array
    {
        $result = [];
        $actions = $this->getActions();
        foreach ($actions as $action) {
            $groups = $this->getGroups($action);
            if ($groups) {
                $result[$action] = $groups;
            }
        }

        return $result;
    }

    /**
     * @return array [action => [[processor id, [attribute name => attribute value, ...]], ...], ...]
     */
    public function getAllProcessors(): array
    {
        $result = [];
        $actions = $this->getActions();
        foreach ($actions as $action) {
            $processors = $this->getProcessors($action);
            if ($processors) {
                $result[$action] = $processors;
            }
        }

        return $result;
    }

    /**
     * Registers a processing group.
     */
    public function addGroup(string $group, string $action, int $priority = 0): void
    {
        $this->assertNotFrozen();

        if (!empty($this->initialData[self::GROUPS][$action])) {
            foreach ($this->initialData[self::GROUPS][$action] as $existingGroup => $existingPriority) {
                if ($group !== $existingGroup && $priority === $existingPriority) {
                    throw new \InvalidArgumentException(sprintf(
                        'The priority %s cannot be used for the group "%s"'
                        . ' because the group with this priority already exists.'
                        . ' Existing group: "%s". Action: "%s".',
                        $priority,
                        $group,
                        $existingGroup,
                        $action
                    ));
                }
            }
        }

        $this->initialData[self::GROUPS][$action][$group] = $priority;
    }

    /**
     * Registers a processor.
     */
    public function addProcessor(
        string $processorId,
        array $attributes,
        string $action = null,
        string $group = null,
        int $priority = 0
    ): void {
        $this->assertNotFrozen();

        if (null === $action) {
            $action = self::NO_ACTION;
        }
        if (!empty($group)) {
            $attributes[self::GROUP_ATTRIBUTE] = $group;
        }

        $this->initialData[self::PROCESSORS][$action][$priority][] = [$processorId, $attributes];
    }

    /**
     * Checks whether the processor bag can be modified
     */
    private function assertNotFrozen(): void
    {
        if (null === $this->initialData) {
            throw new \RuntimeException('The ProcessorBag is frozen.');
        }
    }

    /**
     * Makes sure that $this->groups and $this->processors are initialized
     */
    private function ensureInitialized(): void
    {
        if (null !== $this->initialData) {
            $this->buildProcessors();
            $this->buildGroups();
            $this->initialData = null;
        }
    }

    /**
     * Initializes $this->groups
     */
    private function buildGroups(): void
    {
        $this->groups = [];
        $allGroups = $this->initialData[self::GROUPS] ?? [];
        foreach ($allGroups as $action => $groups) {
            arsort($groups);
            $this->groups[$action] = array_keys($groups);
        }
    }

    /**
     * Initializes $this->processors
     */
    private function buildProcessors(): void
    {
        $this->processors = [];

        if (!empty($this->initialData[self::PROCESSORS])) {
            $groups = [];
            if (!empty($this->initialData[self::GROUPS])) {
                $groups = $this->initialData[self::GROUPS];
            }

            $startCommonProcessors = [];
            $endCommonProcessors = [];
            if (!empty($this->initialData[self::PROCESSORS][self::NO_ACTION])) {
                foreach ($this->initialData[self::PROCESSORS][self::NO_ACTION] as $priority => $priorityData) {
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
                unset($this->initialData[self::PROCESSORS][self::NO_ACTION]);
            }

            foreach ($this->initialData[self::PROCESSORS] as $action => $actionData) {
                $this->processors[$action] = array_merge(
                    $startCommonProcessors,
                    $this->getSortedProcessors($action, $actionData, $groups),
                    $endCommonProcessors
                );
            }
        }
    }

    private function getSortedProcessors(string $action, array $actionData, array $groups): array
    {
        $processors = [];
        foreach ($actionData as $priority => $priorityData) {
            foreach ($priorityData as $processor) {
                if (isset($processor[1][self::GROUP_ATTRIBUTE])) {
                    $group = $processor[1][self::GROUP_ATTRIBUTE];
                    if (!isset($groups[$action][$group])) {
                        throw new \RuntimeException(sprintf(
                            'The group "%s" is not defined. Processor: "%s". Action: "%s".',
                            $group,
                            $processor[0],
                            $action
                        ));
                    }
                    $groupPriority = $groups[$action][$group];
                    $processorPriority = self::calculatePriority($priority, $groupPriority);
                } else {
                    $processorPriority = self::calculatePriority($priority);
                }
                $processors[$processorPriority][] = $processor;
            }
        }
        $processors = $this->sortByPriorityAndFlatten($processors);

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
    private function sortByPriorityAndFlatten(array $items): array
    {
        if (!empty($items)) {
            krsort($items);
            $items = array_merge(...array_values($items));
        }

        return $items;
    }

    /**
     * Calculates an internal priority of a processor based on its priority and a priority of its group.
     */
    private static function calculatePriority(int $processorPriority, int $groupPriority = null): int
    {
        if (null === $groupPriority) {
            if ($processorPriority < 0) {
                $processorPriority += self::getIntervalPriority(-255, -255) + 1;
            } else {
                $processorPriority += self::getIntervalPriority(255, 255) + 2;
            }
        } else {
            if ($groupPriority < -255 || $groupPriority > 255) {
                throw new \RangeException(sprintf(
                    'The value %d is not valid priority of a group. It must be between -255 and 255.',
                    $groupPriority
                ));
            }
            if ($processorPriority < -255 || $processorPriority > 255) {
                throw new \RangeException(sprintf(
                    'The value %d is not valid priority of a processor. It must be between -255 and 255.',
                    $processorPriority
                ));
            }
        }

        return self::getIntervalPriority($processorPriority, $groupPriority);
    }

    private static function getIntervalPriority(int $processorPriority, ?int $groupPriority): int
    {
        return (($groupPriority ?? 0) * 511) + $processorPriority - 1;
    }
}
