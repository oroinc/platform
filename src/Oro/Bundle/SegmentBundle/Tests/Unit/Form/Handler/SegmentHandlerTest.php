<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Form\Handler\SegmentHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SegmentHandlerTest extends \PHPUnit\Framework\TestCase
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
    protected $managerRegistry;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $staticSegmentManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $translator;

    /**
     * @var Segment| \PHPUnit\Framework\MockObject\MockObject
     */
    protected $entity;

    /**
     * @var SegmentHandler
     */
    protected $handler;

    protected function setUp()
    {
        $this->form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($this->request);
        $this->managerRegistry = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->staticSegmentManager = $this->getMockBuilder(
            'Oro\Bundle\SegmentBundle\Entity\Manager\StaticSegmentManager'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->entity = $this->getMockBuilder('Oro\Bundle\SegmentBundle\Entity\Segment')
            ->disableOriginalConstructor()
            ->getMock();
        $this->handler = new SegmentHandler(
            $this->form,
            $requestStack,
            $this->managerRegistry,
            $this->staticSegmentManager
        );
    }

    protected function tearDown()
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

        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod('POST');

        $this->form->expects($this->once())
            ->method('submit')
            ->with(self::FORM_DATA);

        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $manager = $this->createMock('Doctrine\Common\Persistence\ObjectManager');
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

        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod($method);

        $this->form->expects($this->once())->method('submit')
            ->with(self::FORM_DATA);

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
