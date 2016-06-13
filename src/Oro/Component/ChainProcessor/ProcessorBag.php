<?php

namespace Oro\Component\ChainProcessor;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ProcessorBag implements ProcessorBagInterface
{
    /** @var ProcessorFactoryInterface */
    protected $processorFactory;

    /** @var bool */
    protected $debug;

    /**
     * @var array|null
     * after the bag is initialized this property is set to NULL,
     * this switches the bag in "frozen" state and further modification of it is prohibited
     */
    private $initialData = [];

    /**
     * @var array
     *  [
     *      action => [
     *          [
     *              'processor'  => processorId,
     *              'attributes' => [key => value, ...]
     *          ],
     *          ...
     *      ],
     *      ...
     *  ]
     */
    private $processors;

    /** @var ChainApplicableChecker */
    private $processorApplicableChecker;

    /**
     * @param ProcessorFactoryInterface $processorFactory
     * @param bool                      $debug
     */
    public function __construct(ProcessorFactoryInterface $processorFactory, $debug = false)
    {
        $this->processorFactory = $processorFactory;
        $this->debug = $debug;
    }

    /**
     * Registers a processing group
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
     * Registers a processor
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

        $this->initialData['processors'][$action][$priority][] = [
            'processor'  => $processorId,
            'attributes' => $attributes
        ];
    }

    /**
     * Registers a processor applicable checker
     *
     * @param ApplicableCheckerInterface $checker
     * @param int                        $priority
     */
    public function addApplicableChecker(ApplicableCheckerInterface $checker, $priority = 0)
    {
        $this->assertNotFrozen();

        $this->initialData['checkers'][$priority][] = $checker;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessors(ContextInterface $context)
    {
        $this->ensureInitialized();

        return new ProcessorIterator(
            $this->processors,
            $context,
            $this->processorApplicableChecker,
            $this->processorFactory
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getActions()
    {
        $this->ensureInitialized();

        return array_keys($this->processors);
    }

    /**
     * {@inheritdoc}
     */
    public function getActionGroups($action)
    {
        $this->ensureInitialized();

        $result = [];
        if (isset($this->processors[$action])) {
            foreach ($this->processors[$action] as $processor) {
                if (!isset($processor['attributes']['group'])) {
                    continue;
                }
                $group = $processor['attributes']['group'];
                if (!in_array($group, $result, true)) {
                    $result[] = $group;
                }
            }
        }

        return $result;
    }

    /**
     * Checks whether the processor bag can be modified
     */
    protected function assertNotFrozen()
    {
        if (null === $this->initialData) {
            throw new \RuntimeException('The ProcessorBag is frozen.');
        }
    }

    /**
     * Makes sure that the processor bag is initialized
     */
    protected function ensureInitialized()
    {
        if (null !== $this->initialData) {
            $this->initializeProcessors();
            $this->initializeProcessorApplicableChecker();

            $this->initialData = null;
        }
    }

    /**
     * Initializes $this->processors
     */
    protected function initializeProcessors()
    {
        $this->processors = [];

        if (!empty($this->initialData['processors'])) {
            $groups = !empty($this->initialData['groups'])
                ? $this->initialData['groups']
                : [];

            $startCommonProcessors = [];
            $endCommonProcessors   = [];
            if (isset($this->initialData['processors'][''])) {
                foreach ($this->initialData['processors'][''] as $priority => $priorityData) {
                    foreach ($priorityData as $processor) {
                        if ($priority < -65535) {
                            $endCommonProcessors[$priority][] = $processor;
                        } elseif ($priority < -255) {
                            throw new \RangeException(
                                sprintf(
                                    'The value %d is not valid priority of a common processor. '
                                    . 'It must be between -255 and 255 for common processors are executed'
                                    . ' before other processors and less than -65535 for common processors '
                                    . 'are executed after other processors.',
                                    $priority
                                )
                            );
                        } else {
                            $startCommonProcessors[$this->calculatePriority($priority)][] = $processor;
                        }
                    }
                }
                $startCommonProcessors = $this->sortByPriorityAndFlatten($startCommonProcessors);
                $endCommonProcessors   = $this->sortByPriorityAndFlatten($endCommonProcessors);
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
    }

    /**
     * @param string $action
     * @param array  $actionData
     * @param array  $groups
     *
     * @return array
     */
    protected function getSortedProcessors($action, $actionData, $groups)
    {
        $processors = [];
        foreach ($actionData as $priority => $priorityData) {
            foreach ($priorityData as $processor) {
                if (isset($processor['attributes']['group'])) {
                    $group = $processor['attributes']['group'];
                    if (!isset($groups[$action][$group])) {
                        throw new \RuntimeException(
                            sprintf(
                                'The group "%s" is not defined. Processor: "%s".',
                                $group,
                                $processor['processor']
                            )
                        );
                    }
                    $processorPriority = $this->calculatePriority($priority, $groups[$action][$group]);
                } else {
                    $processorPriority = $this->calculatePriority($priority);
                }
                $processors[$processorPriority][] = $processor;
            }
        }
        $processors = $this->sortByPriorityAndFlatten($processors);

        return $processors;
    }

    /**
     * Initializes $this->processorApplicableChecker
     */
    protected function initializeProcessorApplicableChecker()
    {
        $this->processorApplicableChecker = $this->createProcessorApplicableChecker();
        $this->registerApplicableChecker(new GroupRangeApplicableChecker());
        $this->registerApplicableChecker(new SkipGroupApplicableChecker());
        $matchApplicableChecker = new MatchApplicableChecker();
        // add the "priority" attribute to the ignore list,
        // as it is added by LoadProcessorsCompilerPass to processors' attributes only in debug mode
        if ($this->debug) {
            $matchApplicableChecker->addIgnoredAttribute('priority');
        }
        $this->registerApplicableChecker($matchApplicableChecker);
        if (!empty($this->initialData['checkers'])) {
            $checkers = $this->sortByPriorityAndFlatten($this->initialData['checkers']);
            foreach ($checkers as $checker) {
                $this->registerApplicableChecker($checker);
            }
        }
    }

    /**
     * @return ChainApplicableChecker
     */
    protected function createProcessorApplicableChecker()
    {
        return new ChainApplicableChecker();
    }

    /**
     * Adds a checker to $this->processorApplicableChecker
     *
     * @param ApplicableCheckerInterface $checker
     */
    protected function registerApplicableChecker(ApplicableCheckerInterface $checker)
    {
        if ($checker instanceof ProcessorBagAwareApplicableCheckerInterface) {
            $checker->setProcessorBag($this);
        }
        $this->processorApplicableChecker->addChecker($checker);
    }

    /**
     * Calculates a real priority of a processor based on its priority and a priority of its group.
     *
     * The calculated priority is between -65535(-0xFFFF) and 65535(0xFFFF).
     * This allows to add ungrouped and common processors before and after processors grouped inside an action.
     * To add ungrouped processors after grouped ones set priority between -65535(-0xFFFF) and -65280(-0xFF00).
     *
     * The following internal ranges is used:
     * from min int to -65536 = common processors are executed after other processors
     *                          as a default behavior is to execute common processors before other processors,
     *                          you have to use these magic numbers as a priority for common processors that
     *                          should be executed after other processors
     * from -65535 to -65280  = ungrouped processors are executed after grouped processors
     *                          as a default behavior is to execute ungrouped processors before grouped processors,
     *                          you have to use these magic numbers as a priority for ungrouped processors that
     *                          should be executed after grouped processors
     * from -65279 to 65023   = grouped processors
     *                          actually you will use numbers between -255 and 255 for processors' priority
     *                          and numbers between -254 and 252 for groups' priority
     * from 65025 to 65535    = ungrouped processors are executed before grouped processors
     *                          actually for such processors you will use numbers between -255 and 255
     *                          for processors' priority, because ungrouped processors are executed
     *                          before grouped processors by default
     * from 65536 to 66046    = common processors are executed before other processors
     *                          actually for such processors you will use numbers between -255 and 255
     *                          for processors' priority, because common processors are executed
     *                          before other processors by default
     *
     * @param int      $processorPriority
     * @param int|null $groupPriority
     *
     * @return int
     */
    protected function calculatePriority($processorPriority, $groupPriority = null)
    {
        if (null !== $groupPriority && ($groupPriority < -254 || $groupPriority > 252)) {
            throw new \RangeException(
                sprintf(
                    'The value %d is not valid priority of a group. It must be between -254 and 252.',
                    $groupPriority
                )
            );
        }
        $isValidProcessorPriority = ($processorPriority >= -255 && $processorPriority <= 255);
        if (!$isValidProcessorPriority && null === $groupPriority) {
            $isValidProcessorPriority = ($processorPriority >= -65535 && $processorPriority <= -65280);
        }
        if (!$isValidProcessorPriority) {
            throw new \RangeException(
                sprintf(
                    'The value %d is not valid priority of a processor. '
                    . 'It must be between -255 and 255. Also it can be between -65535 and -65280 '
                    . 'if you need to execute ungrouped processor after grouped processors '
                    . 'and less than -65535 if you need to execute common processor after other processors.',
                    $processorPriority
                )
            );
        }

        if (null === $groupPriority) {
            $groupPriority = ($processorPriority >= -65535 && $processorPriority <= -65280)
                ? 0
                : 255;
        } elseif ($groupPriority >= 0) {
            $groupPriority++;
        }

        return $processorPriority + ($groupPriority * 256);
    }

    /**
     * @param array $items
     *
     * @return array
     */
    protected function sortByPriorityAndFlatten(array $items)
    {
        if (empty($items)) {
            return [];
        }

        krsort($items);
        $items = call_user_func_array('array_merge', $items);

        return $items;
    }
}
