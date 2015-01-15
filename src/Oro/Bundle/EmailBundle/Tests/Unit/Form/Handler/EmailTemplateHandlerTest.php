<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Handler;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Form\Handler\EmailTemplateHandler;

class EmailTemplateHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $form;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $manager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
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

    protected function setUp()
    {
        $this->form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = new Request();
        $this->manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entity  = new EmailTemplate();
        $this->handler = new EmailTemplateHandler($this->form, $this->request, $this->manager, $this->translator);
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

        $this->request->setMethod($method);

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);

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

        $this->request->setMethod('POST');

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);

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
     * @dataProvider localeDataProvider
     *
     * @param string|null $defaultLocale
     * @param int|null    $id
     * @param bool        $expectedRefresh
     */
    public function testShouldPresetDefaultLocale($defaultLocale, $id, $expectedRefresh)
    {
        $this->form->expects($this->once())->method('setData')->with($this->entity);

        $this->manager->expects($expectedRefresh ? $this->once() : $this->never())->method('refresh')
            ->with($this->entity);

        $this->setEntityId($id);
        $this->handler->setDefaultLocale($defaultLocale);
        $this->handler->process($this->entity);

        $this->assertSame($defaultLocale, $this->entity->getLocale());
    }

    /**
     * @return array
     */
    public function localeDataProvider()
    {
        return [
            'Should preset default locale '                   => [
                '$defaultLocale'   => 'ru_RU',
                '$id'              => null,
                '$expectedRefresh' => false
            ],
            'Should preset default locale and refresh entity' => [
                '$defaultLocale'   => 'ru_RU',
                '$id'              => null,
                '$expectedRefresh' => false
            ],
        ];
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
