<?php

namespace Oro\Component\Action\Tests\Unit\Model;

use Oro\Component\Action\Event\ExtendableConditionEvent;
use Oro\Component\Action\Model\ExtendableConditionEventErrorsProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExtendableConditionEventErrorsProcessorTest extends TestCase
{
    private RequestStack|MockObject $requestStack;

    private ExtendableConditionEventErrorsProcessor $errorsProcessor;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(fn ($str) => $str . ' TR');

        $this->errorsProcessor = new ExtendableConditionEventErrorsProcessor(
            $this->translator,
            $this->requestStack
        );
    }

    public function testProcessErrorsWithShowErrors(): void
    {
        $event = new ExtendableConditionEvent();
        $event->addError('error.key1');
        $event->addError('error.key2');

        $session = $this->createMock(FlashBagAwareSessionInterface::class);
        $flashBag = $this->createMock(FlashBagInterface::class);

        $session->expects($this->any())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $flashBag->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                ['error', 'error.key1 TR'],
                ['error', 'error.key2 TR']
            );

        $this->requestStack->expects($this->any())
            ->method('getSession')
            ->willReturn($session);

        $errorsCollection = [];
        $errors = $this->errorsProcessor->processErrors($event, true, $errorsCollection, 'error');

        $this->assertEquals(['error.key1 TR', 'error.key2 TR'], $errors);
        $this->assertEmpty($errorsCollection);
    }

    public function testProcessErrorsWithoutShowErrors(): void
    {
        $context = $this->createMock(ConstraintViolationInterface::class);
        $context->expects($this->any())
            ->method('getMessageTemplate')
            ->willReturn('message_template');
        $context->expects($this->any())
            ->method('getMessage')
            ->willReturn('message');
        $context->expects($this->any())
            ->method('getParameters')
            ->willReturn(['param1' => 'value1']);

        $event = new ExtendableConditionEvent();
        $event->addError('error.key1', $context);
        $event->addError('error.key2');

        $this->requestStack->expects($this->never())
            ->method('getSession');

        $errorsCollection = [];
        $errors = $this->errorsProcessor->processErrors($event, false, $errorsCollection, 'error');

        $this->assertEquals(['message', 'error.key2 TR'], $errors);
        $this->assertEquals(
            [
                ['message' => 'message_template', 'parameters' => ['param1' => 'value1']],
                ['message' => 'error.key2', 'parameters' => []]
            ],
            $errorsCollection
        );
    }

    public function testProcessErrorsWithoutErrors(): void
    {
        $event = new ExtendableConditionEvent();

        $errorsCollection = [];
        $errors = $this->errorsProcessor->processErrors($event, true, $errorsCollection);

        $this->assertEmpty($errors);
        $this->assertEmpty($errorsCollection);
    }
}
