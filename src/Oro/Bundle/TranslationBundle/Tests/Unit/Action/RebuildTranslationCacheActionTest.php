<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Action;

use Oro\Bundle\TranslationBundle\Action\RebuildTranslationCacheAction;
use Oro\Bundle\TranslationBundle\Cache\RebuildTranslationCacheHandlerInterface;
use Oro\Bundle\TranslationBundle\Cache\RebuildTranslationCacheResult;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Contracts\Translation\TranslatorInterface;

class RebuildTranslationCacheActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var RebuildTranslationCacheHandlerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $rebuildTranslationCacheHandler;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var RebuildTranslationCacheAction */
    private $action;

    protected function setUp(): void
    {
        $this->rebuildTranslationCacheHandler = $this->createMock(RebuildTranslationCacheHandlerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->action = new RebuildTranslationCacheAction(
            new ContextAccessor(),
            $this->rebuildTranslationCacheHandler,
            $this->translator
        );
        $this->action->setDispatcher($this->createMock(EventDispatcherInterface::class));
    }

    public function testInitializeWithoutAttributeParameter()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Parameter "attribute" is required.');

        $this->action->initialize([]);
    }

    public function testInitializeWithEmptyAttributeParameter()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Parameter "attribute" is required.');

        $this->action->initialize(['attribute' => '']);
    }

    public function testInitializeWithNotValidAttributeParameter()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Parameter "attribute" must be valid property definition.');

        $this->action->initialize(['attribute' => 'test']);
    }

    public function testExecuteWhenRebuildCacheSuccess()
    {
        $this->action->initialize(['attribute' => new PropertyPath('attribute_path')]);

        $this->rebuildTranslationCacheHandler->expects(self::once())
            ->method('rebuildCache')
            ->willReturn(new RebuildTranslationCacheResult(true));

        $this->translator->expects(self::never())
            ->method('trans');

        $context = new ItemStub();
        $this->action->execute($context);

        self::assertSame(
            ['attribute_path' => ['successful' => true, 'message' => null]],
            $context->getData()
        );
    }

    public function testExecuteWhenRebuildCacheFailedWithoutConcreteFailureMessage()
    {
        $defaultFailureMessage = 'default failure message';

        $this->action->initialize(['attribute' => new PropertyPath('attribute_path')]);

        $this->rebuildTranslationCacheHandler->expects(self::once())
            ->method('rebuildCache')
            ->willReturn(new RebuildTranslationCacheResult(false));

        $this->translator->expects(self::once())
            ->method('trans')
            ->with('oro.translation.translation.message.rebuild_cache_failure')
            ->willReturn($defaultFailureMessage);

        $context = new ItemStub();
        $this->action->execute($context);

        self::assertSame(
            ['attribute_path' => ['successful' => false, 'message' => $defaultFailureMessage]],
            $context->getData()
        );
    }

    public function testExecuteWhenRebuildCacheFailedWithConcreteFailureMessage()
    {
        $failureMessage = 'default failure message';

        $this->action->initialize(['attribute' => new PropertyPath('attribute_path')]);

        $this->rebuildTranslationCacheHandler->expects(self::once())
            ->method('rebuildCache')
            ->willReturn(new RebuildTranslationCacheResult(false, $failureMessage));

        $this->translator->expects(self::never())
            ->method('trans');

        $context = new ItemStub();
        $this->action->execute($context);

        self::assertSame(
            ['attribute_path' => ['successful' => false, 'message' => $failureMessage]],
            $context->getData()
        );
    }
}
