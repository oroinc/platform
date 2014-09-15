<?php

namespace Oro\Bundle\FormBundle\Tests\Unit;

use Oro\Bundle\FormBundle\Model\UpdateHandler;

class UpdateHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $session;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $router;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var UpdateHandler
     */
    protected $handler;

    protected function setUp()
    {
        $this->request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $this->session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
            ->disableOriginalConstructor()
            ->getMock();
        $this->router = $this->getMockBuilder('Oro\Bundle\UIBundle\Route\Router')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new UpdateHandler(
            $this->request,
            $this->session,
            $this->router,
            $this->doctrineHelper
        );
    }

    public function testBlankDataNoHandler()
    {
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $entity = new \stdClass();
        $expected = $this->assertSaveData($form, $entity);

        $result = $this->handler->handleUpdate(
            $entity,
            $form,
            array('route' => 'test_update'),
            array('route' => 'test_view'),
            'Saved'
        );
        $this->assertEquals($expected, $result);
    }

    public function testSaveFormInvalid()
    {
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $entity = new \stdClass();
        $this->request->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('POST'));
        $form->expects($this->once())
            ->method('submit')
            ->with($this->request);
        $form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(false));

        $expected = $this->assertSaveData($form, $entity);

        $result = $this->handler->handleUpdate(
            $entity,
            $form,
            array('route' => 'test_update'),
            array('route' => 'test_view'),
            'Saved'
        );
        $this->assertEquals($expected, $result);
    }

    public function testSaveFormValid()
    {
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $entity = new \stdClass();
        $this->request->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('POST'));
        $form->expects($this->once())
            ->method('submit')
            ->with($this->request);
        $form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('persist')
            ->with($entity);
        $em->expects($this->once())
            ->method('flush');
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with($entity)
            ->will($this->returnValue($em));
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->will($this->returnValue(1));

        $expected = $this->assertSaveData($form, $entity);
        $expected['savedId'] = 1;

        $result = $this->handler->handleUpdate(
            $entity,
            $form,
            array('route' => 'test_update'),
            array('route' => 'test_view'),
            'Saved'
        );
        $this->assertEquals($expected, $result);
    }

    public function testSaveHandler()
    {
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $entity = new \stdClass();

        $handler = $this->getMockBuilder('Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\HandlerStub')
            ->getMock();
        $handler->expects($this->once())
            ->method('process')
            ->with($entity)
            ->will($this->returnValue(true));
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->will($this->returnValue(1));

        $expected = $this->assertSaveData($form, $entity);
        $expected['savedId'] = 1;

        $result = $this->handler->handleUpdate(
            $entity,
            $form,
            array('route' => 'test_update'),
            array('route' => 'test_view'),
            'Saved',
            $handler
        );
        $this->assertEquals($expected, $result);
    }

    public function testResultCallback()
    {
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $entity = new \stdClass();

        $handler = $this->getMockBuilder('Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\HandlerStub')
            ->getMock();
        $handler->expects($this->once())
            ->method('process')
            ->with($entity)
            ->will($this->returnValue(true));
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->will($this->returnValue(1));

        $expected = $this->assertSaveData($form, $entity);
        $expected['savedId'] = 1;

        $result = $this->handler->handleUpdate(
            $entity,
            $form,
            array('route' => 'test_update'),
            array('route' => 'test_view'),
            'Saved',
            $handler
        );
        $this->assertEquals($expected, $result);
    }

    public function testSaveHandlerRouteCallback()
    {
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();

        $entity = new \stdClass();

        $handler = $this->getMockBuilder('Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\HandlerStub')
            ->getMock();
        $handler->expects($this->once())
            ->method('process')
            ->with($entity)
            ->will($this->returnValue(true));
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->will($this->returnValue(1));

        $saveAndStayRoute = array('route' => 'test_update');
        $saveAndCloseRoute = array('route' => 'test_view');
        $saveAndStayCallback = function () use ($saveAndStayRoute) {
            return $saveAndStayRoute;
        };
        $saveAndCloseCallback = function () use ($saveAndCloseRoute) {
            return $saveAndCloseRoute;
        };
        $called = false;
        $expectedForm = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $resultCallback = function () use (&$called, $expectedForm) {
            $called = true;
            return array('form' => $expectedForm, 'test' => 1);
        };

        $expected = $this->assertSaveData($form, $entity);
        $expected['savedId'] = 1;
        $expected['test'] = 1;
        $expected['form'] = $expectedForm;

        $result = $this->handler->handleUpdate(
            $entity,
            $form,
            $saveAndStayCallback,
            $saveAndCloseCallback,
            'Saved',
            $handler,
            $resultCallback
        );
        $this->assertTrue($called);
        $this->assertEquals($expected, $result);
    }

    public function testHandleSaveNoWid()
    {
        $message = 'Saved';
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $entity = new \stdClass();
        $handler = $this->getMockBuilder('Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\HandlerStub')
            ->getMock();
        $handler->expects($this->once())
            ->method('process')
            ->with($entity)
            ->will($this->returnValue(true));

        $flashBag = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface')
            ->getMock();
        $flashBag->expects($this->once())
            ->method('add')
            ->with('success', $message);
        $this->session->expects($this->once())
            ->method('getFlashBag')
            ->will($this->returnValue($flashBag));

        $saveAndStayRoute = array('route' => 'test_update');
        $saveAndCloseRoute = array('route' => 'test_view');
        $expected = array('redirect' => true);
        $this->router->expects($this->once())
            ->method('redirectAfterSave')
            ->with($saveAndStayRoute, $saveAndCloseRoute, $entity)
            ->will($this->returnValue($expected));

        $result = $this->handler->handleUpdate(
            $entity,
            $form,
            $saveAndStayRoute,
            $saveAndCloseRoute,
            $message,
            $handler
        );
        $this->assertEquals($expected, $result);
    }

    protected function assertSaveData($form, $entity, $wid = 'WID')
    {
        $this->request->expects($this->atLeastOnce())
            ->method('get')
            ->with('_wid', false)
            ->will($this->returnValue($wid));
        $formView = $this->getMockBuilder('Symfony\Component\Form\FormView')
            ->disableOriginalConstructor()
            ->getMock();
        $form->expects($this->any())
            ->method('createView')
            ->will($this->returnValue($formView));

        return array(
            'entity' => $entity,
            'form'   => $formView,
            'isWidgetContext' => true
        );
    }
}
