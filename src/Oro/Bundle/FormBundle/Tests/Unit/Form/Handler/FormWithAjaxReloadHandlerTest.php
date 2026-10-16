<?php

declare(strict_types=1);

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\FormBundle\Event\FormHandler\Events;
use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;
use Oro\Bundle\FormBundle\Form\Handler\FormWithAjaxReloadHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

final class FormWithAjaxReloadHandlerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private Form&MockObject $form;
    private FormWithAjaxReloadHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->form = $this->createMock(Form::class);
        $this->form->expects(self::any())
            ->method('getName')
            ->willReturn('form_name');

        $this->handler = new FormWithAjaxReloadHandler($this->doctrine, $this->eventDispatcher);
    }

    /**
     * @dataProvider submitMethodDataProvider
     */
    public function testProcessSavesValidFormAndDispatchesEvents(string $method): void
    {
        $data = new \stdClass();
        $request = Request::create('/', $method, ['form_name' => ['field' => 'value']]);

        $this->form->expects(self::once())
            ->method('setData')
            ->with(self::identicalTo($data))
            ->willReturnSelf();
        $this->form->expects(self::once())
            ->method('submit')
            ->with(['field' => 'value'], true)
            ->willReturnSelf();
        $this->form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);
        $this->form->expects(self::never())
            ->method('clearErrors');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('beginTransaction');
        $entityManager->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($data));
        $entityManager->expects(self::once())
            ->method('flush');
        $entityManager->expects(self::once())
            ->method('commit');
        $entityManager->expects(self::never())
            ->method('rollback');
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(\stdClass::class)
            ->willReturn($entityManager);

        $dispatchedEvents = [];
        $dispatchedEventNames = [];
        $this->eventDispatcher->expects(self::exactly(4))
            ->method('dispatch')
            ->willReturnCallback(
                function (object $event, string $eventName) use (&$dispatchedEvents, &$dispatchedEventNames) {
                    $dispatchedEvents[] = $event;
                    $dispatchedEventNames[] = $eventName;

                    return $event;
                }
            );

        self::assertTrue($this->handler->process($data, $this->form, $request));
        self::assertEquals(
            [
                new FormProcessEvent($this->form, $data),
                new FormProcessEvent($this->form, $data),
                new AfterFormProcessEvent($this->form, $data),
                new AfterFormProcessEvent($this->form, $data),
            ],
            $dispatchedEvents
        );
        self::assertSame(
            [
                Events::BEFORE_FORM_DATA_SET,
                Events::BEFORE_FORM_SUBMIT,
                Events::BEFORE_FLUSH,
                Events::AFTER_FLUSH,
            ],
            $dispatchedEventNames
        );
    }

    public function testProcessRollsBackTransactionAndRethrowsExceptionWhenFlushFails(): void
    {
        $data = new \stdClass();
        $request = Request::create('/', Request::METHOD_POST, ['form_name' => ['field' => 'value']]);
        $exception = new \RuntimeException('Flush failed.');

        $this->form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('beginTransaction');
        $entityManager->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($data));
        $entityManager->expects(self::once())
            ->method('flush')
            ->willThrowException($exception);
        $entityManager->expects(self::never())
            ->method('commit');
        $entityManager->expects(self::once())
            ->method('rollback');
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(\stdClass::class)
            ->willReturn($entityManager);

        $this->eventDispatcher->expects(self::exactly(3))
            ->method('dispatch')
            ->willReturnArgument(0);

        $this->expectExceptionObject($exception);

        $this->handler->process($data, $this->form, $request);
    }

    public function testProcessRollsBackTransactionAndRethrowsExceptionWhenAfterFlushListenerFails(): void
    {
        $data = new \stdClass();
        $request = Request::create('/', Request::METHOD_POST, ['form_name' => ['field' => 'value']]);
        $exception = new \RuntimeException('Listener failed.');

        $this->form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('beginTransaction');
        $entityManager->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($data));
        $entityManager->expects(self::once())
            ->method('flush');
        $entityManager->expects(self::never())
            ->method('commit');
        $entityManager->expects(self::once())
            ->method('rollback');
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(\stdClass::class)
            ->willReturn($entityManager);

        $this->eventDispatcher->expects(self::exactly(4))
            ->method('dispatch')
            ->willReturnCallback(function (object $event, string $eventName) use ($exception) {
                if (Events::AFTER_FLUSH === $eventName) {
                    throw $exception;
                }

                return $event;
            });

        $this->expectExceptionObject($exception);

        $this->handler->process($data, $this->form, $request);
    }

    /**
     * @dataProvider submitMethodDataProvider
     */
    public function testProcessReloadsFormWithoutSavingAndClearsErrors(string $method): void
    {
        $data = new \stdClass();
        $request = Request::create(
            '/',
            $method,
            ['form_name' => ['field' => 'value'], FormWithAjaxReloadHandler::WITHOUT_SAVING_KEY => 'true']
        );

        $this->form->expects(self::once())
            ->method('setData')
            ->with(self::identicalTo($data));
        $this->form->expects(self::once())
            ->method('submit')
            ->with(['field' => 'value'], true);
        $this->form->expects(self::once())
            ->method('clearErrors')
            ->with(true);
        $this->form->expects(self::never())
            ->method('isValid');
        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        $dispatchedEventNames = [];
        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function (object $event, string $eventName) use (&$dispatchedEventNames) {
                $dispatchedEventNames[] = $eventName;

                return $event;
            });

        self::assertFalse($this->handler->process($data, $this->form, $request));
        self::assertSame([Events::BEFORE_FORM_DATA_SET, Events::BEFORE_FORM_SUBMIT], $dispatchedEventNames);
    }

    public function testProcessDoesNotSaveInvalidForm(): void
    {
        $data = new \stdClass();
        $request = Request::create('/', Request::METHOD_POST, ['form_name' => ['field' => 'value']]);

        $this->form->expects(self::once())
            ->method('submit')
            ->with(['field' => 'value'], true);
        $this->form->expects(self::once())
            ->method('isValid')
            ->willReturn(false);
        $this->form->expects(self::never())
            ->method('clearErrors');
        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        $dispatchedEventNames = [];
        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function (object $event, string $eventName) use (&$dispatchedEventNames) {
                $dispatchedEventNames[] = $eventName;

                return $event;
            });

        self::assertFalse($this->handler->process($data, $this->form, $request));
        self::assertSame([Events::BEFORE_FORM_DATA_SET, Events::BEFORE_FORM_SUBMIT], $dispatchedEventNames);
    }

    public function testProcessOnlySetsDataForNotSubmitRequest(): void
    {
        $data = new \stdClass();
        $request = Request::create('/', Request::METHOD_GET);

        $this->form->expects(self::once())
            ->method('setData')
            ->with(self::identicalTo($data));
        $this->form->expects(self::never())
            ->method('submit');
        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(new FormProcessEvent($this->form, $data), Events::BEFORE_FORM_DATA_SET)
            ->willReturnArgument(0);

        self::assertFalse($this->handler->process($data, $this->form, $request));
    }

    public function testProcessStopsWhenInterruptedBeforeFormDataSet(): void
    {
        $data = new \stdClass();
        $request = Request::create('/', Request::METHOD_POST, ['form_name' => ['field' => 'value']]);

        $this->form->expects(self::never())
            ->method('setData');
        $this->form->expects(self::never())
            ->method('submit');
        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(new FormProcessEvent($this->form, $data), Events::BEFORE_FORM_DATA_SET)
            ->willReturnCallback(function (FormProcessEvent $event) {
                $event->interruptFormProcess();

                return $event;
            });

        self::assertFalse($this->handler->process($data, $this->form, $request));
    }

    public function testProcessStopsWhenInterruptedBeforeFormSubmit(): void
    {
        $data = new \stdClass();
        $request = Request::create('/', Request::METHOD_POST, ['form_name' => ['field' => 'value']]);

        $this->form->expects(self::once())
            ->method('setData')
            ->with(self::identicalTo($data));
        $this->form->expects(self::never())
            ->method('submit');
        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        $dispatchedEventNames = [];
        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function (FormProcessEvent $event, string $eventName) use (&$dispatchedEventNames) {
                $dispatchedEventNames[] = $eventName;
                if (Events::BEFORE_FORM_SUBMIT === $eventName) {
                    $event->interruptFormProcess();
                }

                return $event;
            });

        self::assertFalse($this->handler->process($data, $this->form, $request));
        self::assertSame([Events::BEFORE_FORM_DATA_SET, Events::BEFORE_FORM_SUBMIT], $dispatchedEventNames);
    }

    public static function submitMethodDataProvider(): iterable
    {
        yield 'POST' => [Request::METHOD_POST];
        yield 'PUT' => [Request::METHOD_PUT];
    }
}
