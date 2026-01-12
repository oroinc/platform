<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Unit;

/**
 * Provides message queue testing utilities for unit tests.
 *
 * This trait integrates message queue assertion capabilities into test classes and automatically
 * manages message collector state by clearing collected messages before and after each test method.
 * It ensures a clean message queue state for isolated test execution.
 */
trait MessageQueueExtension
{
    use MessageQueueAssertTrait;

    /**
     * Removes all sent messages.
     *
     * @before
     */
    public function setUpMessageCollector()
    {
        self::getMessageCollector()
            ->clear();
    }

    /**
     * Removes all sent messages.
     *
     * @after
     */
    public function tearDownMessageCollector()
    {
        self::getMessageCollector()
            ->clear();
    }
}
