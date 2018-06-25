<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Action;

use Oro\Bundle\ActionBundle\Action\ResolveDestinationPage;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Resolver\DestinationPageResolver;
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

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->resolver = $this->createMock(DestinationPageResolver::class);
        $this->requestStack = $this->createMock(RequestStack::class);

        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->action = new ResolveDestinationPage(new ContextAccessor(), $this->resolver, $this->requestStack);
        $this->action->setDispatcher($eventDispatcher);
    }

    public function testExecuteWithoutRequest()
    {
        $context = new ActionData();
        $this->action->initialize(['dest1']);

        $this->requestStack->expects($this->once())->method('getMasterRequest')->willReturn(null);
        $this->resolver->expects($this->never())->method('resolveDestinationUrl');

        $this->action->execute($context);

        $this->assertEquals([], $context->toArray());
    }

    public function testExecuteWithDefaultDestination()
    {
        $context = new ActionData([]);
        $this->action->initialize([DestinationPageResolver::DEFAULT_DESTINATION]);

        $this->requestStack->expects($this->once())
            ->method('getMasterRequest')
            ->willReturn(new Request(['originalUrl' => 'example.com']));
        $this->resolver->expects($this->never())->method('resolveDestinationUrl');

        $this->action->execute($context);

        $this->assertEquals(['redirectUrl' => 'example.com'], $context->toArray());
    }

    public function testExecute()
    {
        $entity = (object)[];

        $context = new ActionData(['entity' => $entity]);
        $this->action->initialize(['dest1']);

        $this->requestStack->expects($this->once())
            ->method('getMasterRequest')
            ->willReturn(new Request(['originalUrl' => 'example.com']));

        $this->resolver->expects($this->once())->method('resolveDestinationUrl')
            ->with($entity, 'dest1')
            ->willReturn('test.example.com');

        $this->action->execute($context);

        $this->assertEquals(['entity' => $entity, 'redirectUrl' => 'test.example.com'], $context->toArray());
    }

    public function testExecuteWithEmptyDestinationUrl()
    {
        $entity = (object)[];

        $context = new ActionData(['entity' => $entity]);
        $this->action->initialize(['dest1']);

        $this->requestStack->expects($this->once())
            ->method('getMasterRequest')
            ->willReturn(new Request(['originalUrl' => 'example.com']));

        $this->resolver->expects($this->once())->method('resolveDestinationUrl')
            ->with($entity, 'dest1')
            ->willReturn(null);

        $this->action->execute($context);

        $this->assertEquals(['entity' => $entity], $context->toArray());
    }

    /**
     * @dataProvider executionDataProvider
     * @param array $options
     */
    public function testExecuteWithCustomEntityAndAttributeOptions(array $options)
    {
        $entity1 = (object)[];
        $entity2 = (object)[];

        $context = new ActionData(['entity' => $entity1, 'original' => $entity2]);
        $this->action->initialize($options);

        $this->requestStack->expects($this->once())
            ->method('getMasterRequest')
            ->willReturn(new Request(['originalUrl' => 'example.com']));

        $this->resolver->expects($this->once())->method('resolveDestinationUrl')
            ->with($entity2, 'dest1')
            ->willReturn('test.example.com');

        $this->action->execute($context);

        $this->assertEquals(
            ['entity' => $entity1, 'original' => $entity2, 'result' => 'test.example.com'],
            $context->toArray()
        );
    }

    /**
     * @return \Generator
     */
    public function executionDataProvider()
    {
        yield 'int options keys' => [
            'options' => ['dest1', new PropertyPath('original'), new PropertyPath('result')]
        ];

        yield 'string options keys' => [
            'options' => [
                'destination' =>'dest1',
                'entity' => new PropertyPath('original'),
                'attribute' => new PropertyPath('result')
            ]
        ];
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Entity must be valid property definition.
     */
    public function testInitializeEntityOptionException()
    {
        $this->action->initialize(['test', 'test']);
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Attribute must be valid property definition.
     */
    public function testInitializeAttributeOptionException()
    {
        $this->action->initialize(['test', new PropertyPath('test'), 'test']);
    }
}
