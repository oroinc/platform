<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Action;

use Oro\Bundle\ActionBundle\Action\ResolveDestinationPage;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Resolver\DestinationPageResolver;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyPath;

class ResolveDestinationPageTest extends \PHPUnit\Framework\TestCase
{
    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    protected $requestStack;

    /** @var DestinationPageResolver|\PHPUnit\Framework\MockObject\MockObject */
    protected $resolver;

    /** @var ResolveDestinationPage */
    protected $action;

    protected function setUp(): void
    {
        $this->resolver = $this->createMock(DestinationPageResolver::class);
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->action = new ResolveDestinationPage(new ContextAccessor(), $this->resolver, $this->requestStack);
        $this->action->setDispatcher($this->createMock(EventDispatcherInterface::class));
    }

    public function testExecuteWithoutRequest(): void
    {
        $context = new ActionData();
        $this->action->initialize(['dest1']);

        $this->requestStack->expects(self::once())
            ->method('getMainRequest')
            ->willReturn(null);
        $this->resolver->expects(self::never())
            ->method('resolveDestinationUrl');

        $this->action->execute($context);

        self::assertEquals([], $context->toArray());
    }

    public function testExecuteWithDefaultDestination(): void
    {
        $context = new ActionData([]);
        $this->action->initialize([DestinationPageResolver::DEFAULT_DESTINATION]);

        $this->requestStack->expects(self::once())
            ->method('getMainRequest')
            ->willReturn(new Request(['originalUrl' => 'example.com']));
        $this->resolver->expects(self::never())
            ->method('resolveDestinationUrl');

        $this->action->execute($context);

        self::assertEquals(['redirectUrl' => 'example.com'], $context->toArray());
    }

    public function testExecute(): void
    {
        $entity = (object)[];

        $context = new ActionData(['entity' => $entity]);
        $this->action->initialize(['dest1']);

        $this->requestStack->expects(self::once())
            ->method('getMainRequest')
            ->willReturn(new Request(['originalUrl' => 'example.com']));

        $this->resolver->expects(self::once())
            ->method('resolveDestinationUrl')
            ->with($entity, 'dest1')
            ->willReturn('test.example.com');

        $this->action->execute($context);

        self::assertEquals(['entity' => $entity, 'redirectUrl' => 'test.example.com'], $context->toArray());
    }

    public function testExecuteWithEmptyDestinationUrl(): void
    {
        $entity = (object)[];

        $context = new ActionData(['entity' => $entity]);
        $this->action->initialize(['dest1']);

        $this->requestStack->expects(self::once())
            ->method('getMainRequest')
            ->willReturn(new Request(['originalUrl' => 'example.com']));

        $this->resolver->expects(self::once())
            ->method('resolveDestinationUrl')
            ->with($entity, 'dest1')
            ->willReturn(null);

        $this->action->execute($context);

        self::assertEquals(['entity' => $entity], $context->toArray());
    }

    /**
     * @dataProvider executionDataProvider
     */
    public function testExecuteWithCustomEntityAndAttributeOptions(array $options): void
    {
        $entity1 = (object)[];
        $entity2 = (object)[];

        $context = new ActionData(['entity' => $entity1, 'original' => $entity2]);
        $this->action->initialize($options);

        $this->requestStack->expects(self::once())
            ->method('getMainRequest')
            ->willReturn(new Request(['originalUrl' => 'example.com']));

        $this->resolver->expects(self::once())
            ->method('resolveDestinationUrl')
            ->with($entity2, 'dest1')
            ->willReturn('test.example.com');

        $this->action->execute($context);

        self::assertEquals(
            ['entity' => $entity1, 'original' => $entity2, 'result' => 'test.example.com'],
            $context->toArray()
        );
    }

    public function executionDataProvider(): array
    {
        return [
            'int options keys' => [
                'options' => ['dest1', new PropertyPath('original'), new PropertyPath('result')]
            ],
            'string options keys' => [
                'options' => [
                    'destination' =>'dest1',
                    'entity' => new PropertyPath('original'),
                    'attribute' => new PropertyPath('result')
                ]
            ]
        ];
    }

    public function testInitializeEntityOptionException(): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Entity must be valid property definition.');

        $this->action->initialize(['test', 'test']);
    }

    public function testInitializeAttributeOptionException(): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Attribute must be valid property definition.');

        $this->action->initialize(['test', new PropertyPath('test'), 'test']);
    }
}
