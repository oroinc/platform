<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Functional;

/**
 * It is expected that this trait will be used in classes that have "getContainer" method.
 * E.g. classes derived from Oro\Bundle\TestFrameworkBundle\Test\WebTestCase.
 */
trait MessageQueueExtension
{
    use MessageQueueAssertTrait;

    /**
     * Enables the collecting of messages before each test.
     *
     * @before
     */
    public function setUpMessageCollector()
    {
        self::getMessageCollector()
            ->enable();
    }

    /**
     * Removes all sent messages and disables the collecting of new messages after each test.
     * The disabling of the collector is needed because it is possible that exist
     * functional test that produce messages, but they do not need to test it,
     * and, as result, this extension might not be added to such tests.
     *
     * @after
     */
    public function tearDownMessageCollector()
    {
        self::getMessageCollector()
            ->clear()
            ->disable();
    }
}
