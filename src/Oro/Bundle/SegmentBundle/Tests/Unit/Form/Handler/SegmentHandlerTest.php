<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Form\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\SegmentBundle\Entity\Manager\StaticSegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Form\Handler\SegmentHandler;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SegmentHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const FORM_DATA = ['field' => 'value'];

    /** @var Form|\PHPUnit\Framework\MockObject\MockObject */
    private $form;

    /** @var Request */
    private $request;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $managerRegistry;

    /** @var StaticSegmentManager|\PHPUnit\Framework\MockObject\MockObject */
    private $staticSegmentManager;

    /** @var Segment|\PHPUnit\Framework\MockObject\MockObject */
    private $entity;

    /** @var SegmentHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->form = $this->createMock(Form::class);
        $this->request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($this->request);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->staticSegmentManager = $this->createMock(StaticSegmentManager::class);

        $this->entity = $this->createMock(Segment::class);
        $this->handler = new SegmentHandler(
            $requestStack,
            $this->managerRegistry,
            $this->staticSegmentManager
        );
    }

    public function testProcessUnsupportedRequest(): void
    {
        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->entity);

        $this->form->expects($this->never())
            ->method('submit');

        self::assertFalse($this->handler->process($this->form, $this->entity));
    }

    public function testProcessValidData(): void
    {
        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->entity);

        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod('POST');

        $this->form->expects($this->once())
            ->method('submit')
            ->with(self::FORM_DATA);

        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->once())
            ->method('persist')
            ->with($this->entity);
        $manager->expects($this->once())
            ->method('flush');
        $this->managerRegistry->expects($this->any())
            ->method('getManager')
            ->willReturn($manager);

        $this->entity->expects($this->atLeastOnce())
            ->method('isStaticType')
            ->willReturn(true);

        $this->staticSegmentManager->expects($this->once())
            ->method('run')
            ->with($this->entity);

        self::assertTrue($this->handler->process($this->form, $this->entity));
    }

    /**
     * @dataProvider supportedMethods
     */
    public function testProcessSupportedRequest(string $method): void
    {
        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->entity);

        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod($method);

        $this->form->expects($this->once())
            ->method('submit')
            ->with(self::FORM_DATA);

        self::assertFalse($this->handler->process($this->form, $this->entity));
    }

    public function supportedMethods(): array
    {
        return [
            ['POST'],
            ['PUT']
        ];
    }
}
