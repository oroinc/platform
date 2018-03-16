<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Action;

use Oro\Bundle\ActionBundle\Tests\Unit\Action\ResolveDestinationPageTest as BaseTestCase;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Symfony\Component\HttpFoundation\Request;

class ResolveDestinationPageTest extends BaseTestCase
{
    public function testExecute()
    {
        $entity = (object)[];

        $context = new WorkflowItem([]);
        $context->setEntity($entity);

        $this->action->initialize(['dest1']);

        $this->requestStack->expects($this->once())
            ->method('getMasterRequest')
            ->willReturn(new Request(['originalUrl' => 'example.com']));

        $this->resolver->expects($this->once())->method('resolveDestinationUrl')
            ->with($entity, 'dest1')
            ->willReturn('test.example.com');

        $this->action->execute($context);

        $this->assertEquals(
            ['entity' => $entity, 'data' => [], 'result' => ['redirectUrl' => 'test.example.com']],
            $this->toArray($context)
        );
    }

    /**
     * @param WorkflowItem $context
     * @return array
     */
    protected function toArray(WorkflowItem $context)
    {
        return [
            'entity' => $context->getEntity(),
            'data' => $context->getData()->toArray(),
            'result' => $context->getResult()->toArray(),
        ];
    }
}
