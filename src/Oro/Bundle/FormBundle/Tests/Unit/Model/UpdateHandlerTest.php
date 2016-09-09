<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Model;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Route\Router;
use Oro\Bundle\FormBundle\Model\UpdateHandler;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\FormBundle\Event\FormHandler\Events;
use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UpdateHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Request
     */
    protected $request;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Session
     */
    protected $session;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Router
     */
    protected $router;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $form
     */
    protected $form;

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
        $this->eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->form = $this->getMock('Symfony\Component\Form\FormInterface');

        $this->handler = new UpdateHandler(
            $this->request,
            $this->session,
            $this->router,
            $this->doctrineHelper,
            $this->eventDispatcher
        );
    }

    /**
     * @return object
     */
    protected function getObject()
    {
        return new \stdClass();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Argument $formHandler should be an object with method "process", stdClass given.
     */
    public function testHandleUpdateFailsWhenFormHandlerIsInvalid()
    {
        $this->form->expects($this->never())->method($this->anything());

        $data = $this->getObject();

        $this->handler->update($data, $this->form, 'Saved', new \stdClass());
    }

    public function testHandleUpdateWorksWithBlankDataAndNoHandler()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $entity = $this->getObject();
        $expected = $this->getExpectedSaveData($form, $entity);

        $result = $this->handler->handleUpdate(
            $entity,
            $form,
            ['route' => 'test_update'],
            ['route' => 'test_view'],
            'Saved'
        );
        $this->assertEquals($expected, $result);
    }

    public function testUpdateWorksWithBlankDataAndNoHandler()
    {
        $data = $this->getObject();
        $expected = $this->getExpectedSaveData($this->form, $data);

        $result = $this->handler->update($data, $this->form, 'Saved');
        $this->assertEquals($expected, $result);
    }

    public function testHandleUpdateWorksWithInvalidForm()
    {
        $entity = $this->getObject();
        $this->form->expects($this->once())
            ->method('setData')
            ->with($entity);
        $this->request->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('POST'));
        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(false));

        $this->assertProcessEventsTriggered($this->form, $entity);
        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch');

        $expected = $this->getExpectedSaveData($this->form, $entity);

        $result = $this->handler->handleUpdate(
            $entity,
            $this->form,
            ['route' => 'test_update'],
            ['route' => 'test_view'],
            'Saved'
        );
        $this->assertEquals($expected, $result);
    }

    public function testUpdateWithInvalidForm()
    {
        $data = $this->getObject();
        $this->form->expects($this->once())
            ->method('setData')
            ->with($data);
        $this->request->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('POST'));
        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(false));

        $this->assertProcessEventsTriggered($this->form, $data);
        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch');

        $expected = $this->getExpectedSaveData($this->form, $data);

        $result = $this->handler->update($data, $this->form, 'Saved');
        $this->assertEquals($expected, $result);
    }

    public function testHandleUpdateWorksWithValidForm()
    {
        $entity = $this->getObject();
        $this->form->expects($this->once())
            ->method('setData')
            ->with($entity);
        $this->request->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('POST'));
        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->once())
            ->method('persist')
            ->with($entity);
        $em->expects($this->once())
            ->method('flush');
        $em->expects($this->once())
            ->method('commit');
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with($entity)
            ->will($this->returnValue($em));
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->will($this->returnValue(1));

        $this->assertProcessEventsTriggered($this->form, $entity);
        $this->assertProcessAfterEventsTriggered($this->form, $entity);
        $this->eventDispatcher->expects($this->exactly(4))
            ->method('dispatch');

        $expected = $this->getExpectedSaveData($this->form, $entity);
        $expected['savedId'] = 1;

        $result = $this->handler->handleUpdate(
            $entity,
            $this->form,
            ['route' => 'test_update'],
            ['route' => 'test_view'],
            'Saved'
        );
        $this->assertEquals($expected, $result);
    }

    public function testUpdateWorksWithValidForm()
    {
        $data = $this->getObject();
        $this->form->expects($this->once())
            ->method('setData')
            ->with($data);
        $this->request->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('POST'));
        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->once())
            ->method('persist')
            ->with($data);
        $em->expects($this->once())
            ->method('flush');
        $em->expects($this->once())
            ->method('commit');
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with($data)
            ->will($this->returnValue($em));
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($data)
            ->will($this->returnValue(1));

        $this->assertProcessEventsTriggered($this->form, $data);
        $this->assertProcessAfterEventsTriggered($this->form, $data);
        $this->eventDispatcher->expects($this->exactly(4))
            ->method('dispatch');

        $expected = $this->getExpectedSaveData($this->form, $data);
        $expected['savedId'] = 1;

        $result = $this->handler->update($data, $this->form, 'Saved');
        $this->assertEquals($expected, $result);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Test flush exception
     */
    public function testHandleUpdateWorksWhenFormFlushFailed()
    {
        $entity = $this->getObject();
        $this->form->expects($this->once())
            ->method('setData')
            ->with($entity);
        $this->request->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('POST'));
        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->once())
            ->method('persist')
            ->with($entity);
        $em->expects($this->once())
            ->method('flush')
            ->willThrowException(new \Exception('Test flush exception'));
        $em->expects($this->once())
            ->method('rollback');
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with($entity)
            ->will($this->returnValue($em));

        $this->handler->handleUpdate(
            $entity,
            $this->form,
            ['route' => 'test_update'],
            ['route' => 'test_view'],
            'Saved'
        );
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Test flush exception
     */
    public function testUpdateWorksWhenFormFlushFailed()
    {
        $data = $this->getObject();
        $this->form->expects($this->once())
            ->method('setData')
            ->with($data);
        $this->request->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('POST'));
        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->once())
            ->method('persist')
            ->with($data);
        $em->expects($this->once())
            ->method('flush')
            ->willThrowException(new \Exception('Test flush exception'));
        $em->expects($this->once())
            ->method('rollback');
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with($data)
            ->will($this->returnValue($em));

        $this->handler->update($data, $this->form, 'Saved');
    }

    public function testHandleUpdateBeforeFormDataSetInterrupted()
    {
        $entity = $this->getObject();
        $this->request->expects($this->never())
            ->method('getMethod')
            ->will($this->returnValue('POST'));
        $this->form->expects($this->never())
            ->method('submit')
            ->with($this->request);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(Events::BEFORE_FORM_DATA_SET)
            ->willReturnCallback(
                function ($name, FormProcessEvent $event) {
                    $event->interruptFormProcess();
                }
            );

        $expected = $this->getExpectedSaveData($this->form, $entity);

        $result = $this->handler->handleUpdate(
            $entity,
            $this->form,
            ['route' => 'test_update'],
            ['route' => 'test_view'],
            'Saved'
        );
        $this->assertEquals($expected, $result);
    }

    public function testHandleUpdateInterruptedBeforeFormSubmit()
    {
        $entity = $this->getObject();
        $this->request->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('POST'));
        $this->form->expects($this->never())
            ->method('submit')
            ->with($this->request);

        $this->eventDispatcher->expects($this->at(1))
            ->method('dispatch')
            ->with(Events::BEFORE_FORM_SUBMIT)
            ->willReturnCallback(
                function ($name, FormProcessEvent $event) {
                    $event->interruptFormProcess();
                }
            );

        $expected = $this->getExpectedSaveData($this->form, $entity);

        $result = $this->handler->handleUpdate(
            $entity,
            $this->form,
            ['route' => 'test_update'],
            ['route' => 'test_view'],
            'Saved'
        );
        $this->assertEquals($expected, $result);
    }

    public function testUpdateInterruptedBeforeFormSubmit()
    {
        $data = $this->getObject();
        $this->request->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('POST'));
        $this->form->expects($this->never())
            ->method('submit')
            ->with($this->request);

        $this->eventDispatcher->expects($this->at(1))
            ->method('dispatch')
            ->with(Events::BEFORE_FORM_SUBMIT)
            ->willReturnCallback(
                function ($name, FormProcessEvent $event) {
                    $event->interruptFormProcess();
                }
            );

        $expected = $this->getExpectedSaveData($this->form, $data);

        $result = $this->handler->update($data, $this->form, 'Saved');
        $this->assertEquals($expected, $result);
    }

    public function testHandleUpdateWorksWithFormHandler()
    {
        $entity = $this->getObject();

        $handler = $this->getHandlerStub($entity);
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->will($this->returnValue(1));

        $expected = $this->getExpectedSaveData($this->form, $entity);
        $expected['savedId'] = 1;

        $result = $this->handler->handleUpdate(
            $entity,
            $this->form,
            ['route' => 'test_update'],
            ['route' => 'test_view'],
            'Saved',
            $handler
        );
        $this->assertEquals($expected, $result);
    }

    public function testHandleUpdateWorksWithRouteCallback()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $form */
        $this->form = $this->getMock('Symfony\Component\Form\FormInterface');

        $entity = $this->getObject();

        $handler = $this->getHandlerStub($entity);
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->will($this->returnValue(1));

        $saveAndStayRoute = ['route' => 'test_update'];
        $saveAndCloseRoute = ['route' => 'test_view'];
        $saveAndStayCallback = function () use ($saveAndStayRoute) {
            return $saveAndStayRoute;
        };
        $saveAndCloseCallback = function () use ($saveAndCloseRoute) {
            return $saveAndCloseRoute;
        };
        $called = false;
        $expectedForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $resultCallback = function () use (&$called, $expectedForm) {
            $called = true;
            return ['form' => $expectedForm, 'test' => 1];
        };

        $expected = $this->getExpectedSaveData($this->form, $entity);
        $expected['savedId'] = 1;
        $expected['test'] = 1;
        $expected['form'] = $expectedForm;

        $result = $this->handler->handleUpdate(
            $entity,
            $this->form,
            $saveAndStayCallback,
            $saveAndCloseCallback,
            'Saved',
            $handler,
            $resultCallback
        );
        $this->assertTrue($called);
        $this->assertEquals($expected, $result);
    }

    public function testHandleUpdateWorksWithoutWid()
    {
        $queryParameters = ['qwe' => 'rty'];
        $this->request->query = new ParameterBag($queryParameters);

        $message = 'Saved';

        $entity = $this->getObject();
        $handler = $this->getHandlerStub($entity);

        $flashBag = $this->getFlashBagMock($message);
        $this->session->expects($this->once())
            ->method('getFlashBag')
            ->will($this->returnValue($flashBag));

        $saveAndStayRoute = ['route' => 'test_update'];
        $saveAndCloseRoute = ['route' => 'test_view'];
        $expected = ['redirect' => true];
        $this->router->expects($this->once())
            ->method('redirectAfterSave')
            ->with(
                array_merge($saveAndStayRoute, ['parameters' => $queryParameters]),
                array_merge($saveAndCloseRoute, ['parameters' => $queryParameters]),
                $entity
            )
            ->will($this->returnValue($expected));

        $result = $this->handler->handleUpdate(
            $entity,
            $this->form,
            $saveAndStayRoute,
            $saveAndCloseRoute,
            $message,
            $handler
        );
        $this->assertEquals($expected, $result);
    }

    public function testUpdateWorksWithoutWid()
    {
        $this->request->query = new ParameterBag(['qwe' => 'rty']);

        $message = 'Saved';

        $data = $this->getObject();
        $handler = $this->getHandlerStub($data);

        $this->session->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($this->getFlashBagMock($message));

        $redirectResponse = new \stdClass();
        $this->router->expects($this->once())
            ->method('redirect')
            ->with($data)
            ->willReturn($redirectResponse);

        $actual = $this->handler->update($data, $this->form, $message, $handler);
        $this->assertEquals($redirectResponse, $actual);
    }

    public function testUpdateWorksWithoutFormHandler()
    {
        $data = $this->getObject();

        $handler = $this->getHandlerStub($data);
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($data)
            ->will($this->returnValue(1));

        $expected = $this->getExpectedSaveData($this->form, $data);
        $expected['savedId'] = 1;

        $result = $this->handler->update($data, $this->form, 'Saved', $handler);
        $this->assertEquals($expected, $result);
    }

    /**
     * @param string $message
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getFlashBagMock($message)
    {
        $flashBag = $this->getMock('Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface');
        $flashBag->expects($this->once())
            ->method('add')
            ->with('success', $message);

        return $flashBag;
    }

    /**
     * @param object $entity
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getHandlerStub($entity)
    {
        $handler = $this->getMock('Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\HandlerStub');
        $handler->expects($this->once())
            ->method('process')
            ->with($entity)
            ->willReturn(true);

        return $handler;
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject|FormInterface $form
     * @param object $entity
     * @param string $wid
     * @return array
     */
    protected function getExpectedSaveData($form, $entity, $wid = 'WID')
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

        return [
            'entity' => $entity,
            'form' => $formView,
            'isWidgetContext' => true
        ];
    }

    /**
     * @param FormInterface $form
     * @param object $entity
     */
    protected function assertProcessEventsTriggered(FormInterface $form, $entity)
    {
        $this->eventDispatcher->expects($this->at(0))
            ->method('dispatch')
            ->with(Events::BEFORE_FORM_DATA_SET, new FormProcessEvent($form, $entity));

        $this->eventDispatcher->expects($this->at(1))
            ->method('dispatch')
            ->with(Events::BEFORE_FORM_SUBMIT, new FormProcessEvent($form, $entity));
    }

    /**
     * @param FormInterface $form
     * @param object $entity
     */
    protected function assertProcessAfterEventsTriggered(FormInterface $form, $entity)
    {
        $this->eventDispatcher->expects($this->at(2))
            ->method('dispatch')
            ->with(Events::BEFORE_FLUSH, new AfterFormProcessEvent($form, $entity));

        $this->eventDispatcher->expects($this->at(3))
            ->method('dispatch')
            ->with(Events::AFTER_FLUSH, new AfterFormProcessEvent($form, $entity));
    }
}
