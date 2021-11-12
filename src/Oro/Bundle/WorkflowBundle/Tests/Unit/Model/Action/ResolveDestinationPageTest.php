<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Action;

use Oro\Bundle\ActionBundle\Tests\Unit\Action\ResolveDestinationPageTest as BaseTestCase;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Symfony\Component\HttpFoundation\Request;

class ResolveDestinationPageTest extends BaseTestCase
{
    public function testExecute(): void
    {
        $entity = (object)[];

        $context = new WorkflowItem();
        $context->setEntity($entity);

        $this->action->initialize(['dest1']);

        $this->requestStack->expects(self::once())
            ->method('getMainRequest')
            ->willReturn(new Request(['originalUrl' => 'example.com']));

        $this->resolver->expects(self::once())
            ->method('resolveDestinationUrl')
            ->with($entity, 'dest1')
            ->willReturn('test.example.com');

        $this->action->execute($context);

        self::assertEquals(
            ['entity' => $entity, 'data' => [], 'result' => ['redirectUrl' => 'test.example.com']],
            $this->toArray($context)
        );
    }

    private function toArray(WorkflowItem $context): array
    {
        return [
            'entity' => $context->getEntity(),
            'data' => $context->getData()->toArray(),
            'result' => $context->getResult()->toArray(),
        ];
    }
}
