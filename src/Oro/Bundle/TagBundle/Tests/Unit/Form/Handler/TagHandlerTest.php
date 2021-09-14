<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Form\Handler;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Form\Handler\TagHandler;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class TagHandlerTest extends \PHPUnit\Framework\TestCase
{
    const FORM_DATA = ['field' => 'value'];

    private FormInterface|\PHPUnit\Framework\MockObject\MockObject $form;

    private Request $request;

    private ObjectManager|\PHPUnit\Framework\MockObject\MockObject $manager;

    private TagHandler $handler;

    private Tag $entity;

    protected function setUp(): void
    {
        $this->form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($this->request);
        $this->manager = $this->getMockBuilder('Doctrine\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entity  = new Tag();
        $this->handler = new TagHandler($this->form, $requestStack, $this->manager);
    }

    public function testProcessUnsupportedRequest(): void
    {
        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->entity);

        $this->form->expects($this->never())
            ->method('submit');

        self::assertFalse($this->handler->process($this->entity));
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

        self::assertFalse($this->handler->process($this->entity));
    }

    public function supportedMethods(): array
    {
        return [
            ['POST'],
            ['PUT'],
        ];
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

        $this->manager->expects($this->once())
            ->method('persist')
            ->with($this->entity);

        $this->manager->expects($this->once())
            ->method('flush');

        self::assertTrue($this->handler->process($this->entity));
    }
}
