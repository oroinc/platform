<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Form\Handler;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Form\Handler\SegmentHandler;

class SegmentHandlerTest extends \PHPUnit_Framework_TestCase
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
     * @var SegmentHandler
     */
    protected $handler;

    /**
     * @var Segment
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

        $this->entity  = new Segment();
        $this->handler = new SegmentHandler($this->form, $this->request, $this->manager);
    }

    public function tearDown()
    {
        unset($this->form, $this->request, $this->manager, $this->handler, $this->entity);
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

    /**
     * @dataProvider supportedMethods
     *
     * @param string $method
     */
    public function testProcessSupportedRequest($method)
    {
        $this->form->expects($this->once())->method('setData')
            ->with($this->entity);

        $this->request->setMethod($method);

        $this->form->expects($this->once())->method('submit')
            ->with($this->request);

        $this->assertFalse($this->handler->process($this->entity));
    }

    /**
     * @return array
     */
    public function supportedMethods()
    {
        return [
            ['POST'],
            ['PUT']
        ];
    }
}
