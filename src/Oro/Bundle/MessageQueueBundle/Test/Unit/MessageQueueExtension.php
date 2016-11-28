<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Unit;

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
