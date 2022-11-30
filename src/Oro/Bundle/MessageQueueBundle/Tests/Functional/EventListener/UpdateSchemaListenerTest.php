<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\EventListener;

use Oro\Bundle\EntityExtendBundle\Event\UpdateSchemaEvent;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\InterruptConsumptionExtension;
use Oro\Bundle\MessageQueueBundle\EventListener\UpdateSchemaListener;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UpdateSchemaListenerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();

        $this->interruptConsumptionCache = self::getContainer()->get('oro_message_queue.interrupt_consumption.cache');
        $this->interruptConsumptionCache->clear();
    }

    protected function tearDown(): void
    {
        $filePath = self::getContainer()->getParameter('oro_message_queue.consumption.interrupt_filepath');

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $updateSchemaListener = self::getContainer()->get('oro_message_queue.listener.update_schema');
        $updateSchemaListener->setInterruptConsumptionCache($this->interruptConsumptionCache);

        $this->interruptConsumptionCache->clear();

        parent::tearDown();
    }

    public function testMustBeListeningForUpdateSchemaEvent(): void
    {
        $dispatcher = $this->getEventDispatcher();

        $isListenerExist = false;
        foreach ($dispatcher->getListeners(UpdateSchemaEvent::NAME) as $listener) {
            if ($listener[0] instanceof UpdateSchemaListener) {
                $isListenerExist = true;
                break;
            }
        }

        self::assertTrue($isListenerExist);
    }

    public function testMustCreateFileIfNotExistOnUpdateSchemaEvent(): void
    {
        $updateSchemaListener = self::getContainer()->get('oro_message_queue.listener.update_schema');
        // Use schema listener without an App cache and with file metadata
        $updateSchemaListener->setInterruptConsumptionCache();
        $filePath = self::getContainer()->getParameter('oro_message_queue.consumption.interrupt_filepath');

        self::assertFileDoesNotExist($filePath);

        $this->removeListenersForEventExceptTested();

        $this->dispatchUpdateSchemaEvent();

        self::assertFileExists($filePath);
    }

    public function testMustUpdateFileMetadataOnUpdateSchemaEvent(): void
    {
        $updateSchemaListener = self::getContainer()->get('oro_message_queue.listener.update_schema');
        // Uses schema listener without an App cache and with file metadata
        $updateSchemaListener->setInterruptConsumptionCache();
        $filePath = self::getContainer()->getParameter('oro_message_queue.consumption.interrupt_filepath');
        $directory = dirname($filePath);

        @mkdir($directory, 0777, true);
        touch($filePath);

        self::assertFileExists($filePath);

        $timestamp = filemtime($filePath);
        sleep(1);

        $this->removeListenersForEventExceptTested();

        $this->dispatchUpdateSchemaEvent();

        clearstatcache(true, $filePath);

        self::assertGreaterThan($timestamp, filemtime($filePath));
    }

    public function testOnSchemaUpdateMustClearCacheItem(): void
    {
        self::getContainer()->get(
            'oro_message_queue.consumption.interrupt_consumption_extension'
        );

        $interruptConsumptionCache = $this->interruptConsumptionCache->getItem(
            InterruptConsumptionExtension::CACHE_KEY
        );

        self::assertTrue($interruptConsumptionCache->isHit());

        $this->removeListenersForEventExceptTested();
        $this->dispatchUpdateSchemaEvent();

        $interruptConsumptionCache = $this->interruptConsumptionCache->getItem(
            InterruptConsumptionExtension::CACHE_KEY
        );

        self::assertFalse($interruptConsumptionCache->isHit());
    }

    /**
     * Remove all listeners except UpdateSchemaListener for UpdateSchemaEvent
     */
    private function removeListenersForEventExceptTested(): void
    {
        $dispatcher = $this->getEventDispatcher();

        foreach ($dispatcher->getListeners(UpdateSchemaEvent::NAME) as $listener) {
            if (!$listener[0] instanceof UpdateSchemaListener) {
                $dispatcher->removeListener(UpdateSchemaEvent::NAME, $listener);
            }
        }
    }

    /**
     * Dispatch UpdateSchemaEvent
     */
    private function dispatchUpdateSchemaEvent(): void
    {
        $dispatcher = $this->getEventDispatcher();

        $event = $this->createMock(UpdateSchemaEvent::class);
        $dispatcher->dispatch($event, UpdateSchemaEvent::NAME);
    }

    private function getEventDispatcher(): EventDispatcherInterface
    {
        return self::getContainer()->get('event_dispatcher');
    }
}
