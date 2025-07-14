<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Action;

use Oro\Bundle\DraftBundle\Action\DraftPublishAction;
use Oro\Bundle\DraftBundle\Duplicator\DraftContext;
use Oro\Bundle\DraftBundle\Manager\DraftManager;
use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class DraftPublishActionTest extends TestCase
{
    use EntityTrait;

    private DraftPublishAction $action;
    private ContextAccessor $contextAccessor;
    private DraftManager&MockObject $draftManager;
    private EventDispatcherInterface&MockObject $eventDispatcher;

    #[\Override]
    protected function setUp(): void
    {
        $this->contextAccessor = new ContextAccessor();
        $this->draftManager = $this->createMock(DraftManager::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->action = new DraftPublishAction($this->contextAccessor, $this->draftManager);
        $this->action->setDispatcher($this->eventDispatcher);
    }

    public function testInitialize(): void
    {
        $this->expectExceptionMessage('The required options "source", "target" are missing.');
        $this->action->initialize([]);

        $options = [
            'source' => new PropertyPath('source'),
            'target' => new PropertyPath('target')
        ];
        $this->assertInstanceOf(ActionInterface::class, $this->action->initialize($options));
    }

    public function testExecute(): void
    {
        $context = new DraftContext();
        $source = $this->getEntity(DraftableEntityStub::class);
        $sourceProperty = new PropertyPath('source');
        $targetProperty = new PropertyPath('target');
        $this->contextAccessor->setValue($context, $sourceProperty, $source);
        $this->contextAccessor->setValue($context, $targetProperty, null);

        $this->draftManager->expects($this->once())
            ->method('createPublication')
            ->willReturn($source);

        $this->action->initialize([
            'source' => new PropertyPath('source'),
            'target' => new PropertyPath('target'),
        ]);
        $this->action->execute($context);
        $expectedTarget = $this->contextAccessor->getValue($context, $targetProperty);
        $this->assertSame($source, $expectedTarget);
    }
}
