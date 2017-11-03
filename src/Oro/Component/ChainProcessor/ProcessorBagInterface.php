<?php

namespace Oro\Component\ChainProcessor;

interface ProcessorBagInterface
{
    /**
     * Registers processing groups.
     * This method was created by performance reasons and it is intended only to set initial set of groups.
     * If the bag already contains any groups all of them will be lost.
     *
     * @param array $groups The set of processing groups
     *                      [
     *                          action => [
     *                              group => priority,
     *                              ...
     *                          ],
     *                          ...
     *                      ]
     */
    public function setGroups(array $groups);

    /**
     * Registers processors.
     * This method was created by performance reasons and it is intended only to set initial set of processors.
     * If the bag already contains any processors all of them will be lost.
     *
     * @param array $processors The set of processors
     *                          [
     *                              action => [
     *                                  priority => [
     *                                      [processor id, [attribute name => attribute value, ...]],
     *                                      ...
     *                                  ],
     *                                  ...
     *                              ],
     *                              ...
     *                          ]
     */
    public function setProcessors(array $processors);

    /**
     * Registers a processing group.
     *
     * @param string $group
     * @param string $action
     * @param int    $priority
     */
    public function addGroup($group, $action, $priority = 0);

    /**
     * Registers a processor.
     *
     * @param string      $processorId
     * @param array       $attributes
     * @param string|null $action
     * @param string|null $group
     * @param int         $priority
     */
    public function addProcessor($processorId, array $attributes, $action = null, $group = null, $priority = 0);

    /**
     * Registers a processor applicable checker.
     *
     * @param ApplicableCheckerInterface $checker
     * @param int                        $priority
     */
    public function addApplicableChecker(ApplicableCheckerInterface $checker, $priority = 0);

    /**
     * Gets an iterator that can be used to iterate through processors applicable to the given context.
     *
     * @param ContextInterface $context
     *
     * @return ProcessorIterator
     */
    public function getProcessors(ContextInterface $context);

    /**
     * Gets all registered actions.
     *
     * @return string[]
     */
    public function getActions();

    /**
     * Gets all groups registered for the given action and have at least one processor.
     * The returned groups are sorted by its priority.
     *
     * @param string $action An action
     *
     * @return string[]
     */
    public function getActionGroups($action);
}
