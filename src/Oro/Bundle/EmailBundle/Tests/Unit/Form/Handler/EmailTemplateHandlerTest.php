<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Handler;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Form\Handler\EmailTemplateHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailTemplateHandlerTest extends TestCase
{
    private const array FORM_DATA = ['field' => 'value'];

    private Form&MockObject $form;
    private Request $request;
    private ObjectManager&MockObject $manager;
    private TranslatorInterface&MockObject $translator;
    private EmailTemplateHandler $handler;
    private EmailTemplate $entity;

    #[\Override]
    protected function setUp(): void
    {
        $this->form = $this->createMock(Form::class);
        $this->request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($this->request);
        $this->manager = $this->createMock(ObjectManager::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->entity = new EmailTemplate();
        $this->handler = new EmailTemplateHandler($this->form, $requestStack, $this->manager, $this->translator);
    }

    public function testProcessUnsupportedRequest(): void
    {
        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->entity);

        $this->form->expects($this->never())
            ->method('submit');

        $this->assertFalse($this->handler->process($this->entity));
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

        $this->assertFalse($this->handler->process($this->entity));
    }

    public function supportedMethods(): array
    {
        return [
            ['POST'],
            ['PUT']
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

        $this->assertTrue($this->handler->process($this->entity));
    }

    public function testAddingErrorToNonEditableSystemEntity(): void
    {
        $this->entity->setIsSystem(true);
        $this->entity->setIsEditable(false);

        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->entity);
        $this->form->expects($this->once())
            ->method('addError');

        $this->request->setMethod('POST');

        $this->translator->expects($this->once())
            ->method('trans')
            ->willReturnCallback(static fn (string $key) => '[trans]' . $key . '[/trans]');

        $this->assertFalse($this->handler->process($this->entity));
    }
}
