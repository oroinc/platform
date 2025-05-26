<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Exception\ForbiddenActionGroupException;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\NormalizeResultContext;
use Oro\Bundle\ApiBundle\Util\ActionGroupExecutor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class ActionGroupExecutorTest extends TestCase
{
    private ActionGroupRegistry&MockObject $actionGroupRegistry;
    private ActionGroupExecutor $actionGroupExecutor;

    #[\Override]
    protected function setUp(): void
    {
        $this->actionGroupRegistry = $this->createMock(ActionGroupRegistry::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($message) {
                return 'Translated: ' . $message;
            });

        $this->actionGroupExecutor = new ActionGroupExecutor(
            $this->actionGroupRegistry,
            $translator
        );
    }

    public function testExecute(): void
    {
        $name = 'test_action_group';
        $data = new ActionData(['param1' => 'val1']);
        $context = new NormalizeResultContext();
        $actionGroup = $this->createMock(ActionGroup::class);

        $this->actionGroupRegistry->expects(self::once())
            ->method('get')
            ->with($name)
            ->willReturn($actionGroup);
        $actionGroup->expects(self::once())
            ->method('execute')
            ->with(self::identicalTo($data), self::isInstanceOf(ArrayCollection::class));

        self::assertTrue(
            $this->actionGroupExecutor->execute($name, $data, $context)
        );
        self::assertFalse($context->hasErrors());
    }

    public function testExecuteWithErrors(): void
    {
        $name = 'test_action_group';
        $data = new ActionData(['param1' => 'val1']);
        $context = new NormalizeResultContext();
        $actionGroup = $this->createMock(ActionGroup::class);

        $this->actionGroupRegistry->expects(self::once())
            ->method('get')
            ->with($name)
            ->willReturn($actionGroup);
        $actionGroup->expects(self::once())
            ->method('execute')
            ->willReturnCallback(function ($data, $errors) {
                /** @var ArrayCollection $errors */
                $errors->add(['message' => 'some_error', 'parameters' => []]);

                return $data;
            });

        self::assertFalse(
            $this->actionGroupExecutor->execute($name, $data, $context)
        );
        self::assertEquals(
            [Error::createValidationError('action constraint', 'Translated: some_error')],
            $context->getErrors()
        );
    }

    public function testExecuteWithErrorsAndCustomErrorTitle(): void
    {
        $name = 'test_action_group';
        $data = new ActionData(['param1' => 'val1']);
        $context = new NormalizeResultContext();
        $actionGroup = $this->createMock(ActionGroup::class);
        $errorTitle = 'another constraint';

        $this->actionGroupRegistry->expects(self::once())
            ->method('get')
            ->with($name)
            ->willReturn($actionGroup);
        $actionGroup->expects(self::once())
            ->method('execute')
            ->willReturnCallback(function ($data, $errors) {
                /** @var ArrayCollection $errors */
                $errors->add(['message' => 'some_error', 'parameters' => []]);

                return $data;
            });

        self::assertFalse(
            $this->actionGroupExecutor->execute($name, $data, $context, $errorTitle)
        );
        self::assertEquals(
            [Error::createValidationError($errorTitle, 'Translated: some_error')],
            $context->getErrors()
        );
    }

    public function testExecuteWithForbiddenActionGroupException(): void
    {
        $name = 'test_action_group';
        $data = new ActionData(['param1' => 'val1']);
        $context = new NormalizeResultContext();
        $actionGroup = $this->createMock(ActionGroup::class);

        $this->actionGroupRegistry->expects(self::once())
            ->method('get')
            ->with($name)
            ->willReturn($actionGroup);
        $actionGroup->expects(self::once())
            ->method('execute')
            ->willThrowException(new ForbiddenActionGroupException('action group forbidden'));

        self::assertFalse(
            $this->actionGroupExecutor->execute($name, $data, $context)
        );
        self::assertEquals(
            [Error::createValidationError('action constraint', 'action group forbidden')],
            $context->getErrors()
        );
    }
}
