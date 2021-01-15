<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Form\Handler\EmailTemplateHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class EmailTemplateHandlerTest extends \PHPUnit\Framework\TestCase
{
    const FORM_DATA = ['field' => 'value'];

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $form;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $manager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $translator;

    /**
     * @var EmailTemplateHandler
     */
    protected $handler;

    /**
     * @var EmailTemplate
     */
    protected $entity;

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
        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entity  = new EmailTemplate();
        $this->handler = new EmailTemplateHandler($this->form, $requestStack, $this->manager, $this->translator);
    }

    public function testProcessUnsupportedRequest()
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
     * @param string $method
     */
    public function testProcessSupportedRequest($method)
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

    public function supportedMethods()
    {
        return array(
            array('POST'),
            array('PUT')
        );
    }

    public function testProcessValidData()
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
            ->will($this->returnValue(true));

        $this->manager->expects($this->once())
            ->method('persist')
            ->with($this->entity);

        $this->manager->expects($this->once())
            ->method('flush');

        $this->assertTrue($this->handler->process($this->entity));
    }

    public function testAddingErrorToNonEditableSystemEntity()
    {
        $this->entity->setIsSystem(true);
        $this->entity->setIsEditable(false);

        $this->form->expects($this->once())->method('setData')
            ->with($this->entity);
        $this->form->expects($this->once())->method('addError');

        $this->request->setMethod('POST');

        $this->translator->expects($this->once())
            ->method('trans');

        $this->assertFalse($this->handler->process($this->entity));
    }

    /**
     * @param int|null $id
     */
    protected function setEntityId($id)
    {
        $ref = new \ReflectionProperty(get_class($this->entity), 'id');
        $ref->setAccessible(true);
        $ref->setValue($this->entity, $id);
    }
}
