<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Action;

use Oro\Bundle\SecurityBundle\Action\GenerateUuidAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class GenerateUuidActionTest extends TestCase
{
    private ContextAccessor&MockObject $contextAccessor;
    private EventDispatcherInterface&MockObject $dispatcher;
    private GenerateUuidAction $action;

    #[\Override]
    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);

        $this->action = new GenerateUuidAction($this->contextAccessor);

        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->action->setDispatcher($this->dispatcher);
    }

    /**
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $options): void
    {
        $this->contextAccessor->expects($this->any())
            ->method('getValue')
            ->willReturnArgument(1);

        $this->contextAccessor->expects($this->once())
            ->method('setValue')
            ->willReturnCallback(function ($context, $attributePath, string $uuid) {
                $this->assertNotEmpty($uuid);
            });

        $this->action->initialize($options);
        $this->action->execute([]);
    }

    public function executeDataProvider(): array
    {
        $attributePath = new PropertyPath('attribute');

        return [
            ['options' => ['attribute' => $attributePath]],
            ['options' => [$attributePath]],
        ];
    }

    public function testInitializeWithoutRequiredField(): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Parameter "attribute" is required');

        $this->action->initialize([]);
    }
}
