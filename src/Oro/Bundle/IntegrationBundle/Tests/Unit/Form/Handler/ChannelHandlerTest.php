<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Form\Handler;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Form\Handler\ChannelHandler;
use Oro\Bundle\IntegrationBundle\Event\DefaultOwnerSetEvent;

class ChannelHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityManager */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var ChannelHandler */
    protected $handler;

    /** @var Channel */
    protected $entity;

    protected function setUp()
    {
        $this->request         = new Request();
        $this->form            = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()->getMock();
        $this->em              = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->entity  = new Channel();
        $this->handler = new ChannelHandler($this->request, $this->form, $this->em, $this->eventDispatcher);
    }

    public function testProcessUnsupportedRequest()
    {
        $this->form->expects($this->once())->method('setData')
            ->with($this->entity);

        $this->form->expects($this->never())->method('submit');

        $this->assertFalse($this->handler->process($this->entity));
    }

    /**
     * @dataProvider supportedMethods
     *
     * @param string $method
     */
    public function testProcessSupportedRequest($method)
    {
        $this->request->setMethod($method);

        $this->form->expects($this->once()) ->method('setData')
            ->with($this->entity);
        $this->form->expects($this->once()) ->method('submit')
            ->with($this->request);

        $this->assertFalse($this->handler->process($this->entity));
    }

    /**
     * @return array
     */
    public function supportedMethods()
    {
        return [['POST', 'PUT']];
    }

    public function testProcessValidData()
    {
        $this->request->setMethod('POST');

        $this->form->expects($this->once())->method('setData')->with($this->entity);
        $this->form->expects($this->once()) ->method('submit') ->with($this->request);
        $this->form->expects($this->once()) ->method('isValid')
            ->will($this->returnValue(true));

        $this->em->expects($this->once()) ->method('persist') ->with($this->entity);
        $this->em->expects($this->once())->method('flush');

        $this->assertTrue($this->handler->process($this->entity));
    }

    /**
     * @dataProvider eventDataProvider
     *
     * @param Channel   $entity
     * @param null|User $newOwner
     * @param bool      $expectedDispatch
     */
    public function testDefaultUserOwnerSetEventDispatching($entity, $newOwner, $expectedDispatch)
    {
        $this->request->setMethod('POST');

        $this->form->expects($this->once())->method('setData')->with($entity)
            ->will(
                $this->returnCallback(
                    function ($entity) use ($newOwner) {
                        $entity->setDefaultUserOwner($newOwner);
                    }
                )
            );
        $this->form->expects($this->once()) ->method('submit') ->with($this->request);
        $this->form->expects($this->once()) ->method('isValid')
            ->will($this->returnValue(true));

        $this->em->expects($this->once()) ->method('persist') ->with($entity);
        $this->em->expects($this->once())->method('flush');

        if ($expectedDispatch) {
            $this->eventDispatcher->expects($this->once())->method('dispatch')
                ->with(
                    $this->equalTo(DefaultOwnerSetEvent::NAME),
                    $this->isInstanceOf('Oro\Bundle\IntegrationBundle\Event\DefaultOwnerSetEvent')
                );
        } else {
            $this->eventDispatcher->expects($this->never())->method('dispatch');
        }

        $this->assertTrue($this->handler->process($entity));
    }

    /**
     * @return array
     */
    public function eventDataProvider()
    {
        $newChannel = new Channel();
        $newOwner   = $this->getMock('Oro\Bundle\UserBundle\Entity\User');

        $refProperty = new \ReflectionProperty('Oro\Bundle\IntegrationBundle\Entity\Channel', 'id');
        $refProperty->setAccessible(true);

        $oldChannel = new Channel();
        $refProperty->setValue($oldChannel, 123);

        $someOwner           = $this->getMock('Oro\Bundle\UserBundle\Entity\User');
        $oldChannelWithOwner = clone $oldChannel;
        $oldChannelWithOwner->setDefaultUserOwner($someOwner);

        return [
            'new entity, should not dispatch'              => [$newChannel, $newOwner, false],
            'channel is not new, but owner existed before' => [$oldChannelWithOwner, $newOwner, false],
            'old channel without owner, should dispatch'   => [$oldChannel, $newOwner, true]
        ];
    }
}
