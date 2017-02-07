<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Functional;

/**
 * It is expected that this trait will be used in classes that have "getContainer" static method.
 * E.g. classes derived from Oro\Bundle\TestFrameworkBundle\Test\WebTestCase.
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
        $this->clearMessageCollector();
    }

    /**
     * Removes all sent messages.
     *
     * After triggered after client removed
     */
    public function tearDown()
    {
        $this->clearMessageCollector();
    }

    public function clearMessageCollector()
    {
        self::getMessageCollector()->clear();
    }
}
